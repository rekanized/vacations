<?php

namespace App\Http\Controllers;

use App\Models\Absence;
use App\Models\AbsenceRequestLog;
use App\Models\AbsenceOption;
use App\Models\Setting;
use App\Models\User;
use App\Support\AzureAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Models\Department;

class AdminController extends Controller
{
    public function index(Request $request): View
    {
        return $this->settings($request);
    }

    public function settings(Request $request): View
    {
        $absenceOptionUsage = $this->absenceOptionUsageByCode();

        return view('admin.index', [
            ...$this->baseAdminViewData($request),
            'absenceOptions' => AbsenceOption::query()
                ->orderBy('sort_order')
                ->orderBy('label')
                ->get()
                ->map(function (AbsenceOption $option) use ($absenceOptionUsage) {
                    $usage = $absenceOptionUsage->get($option->code);

                    $option->setAttribute('absence_count', (int) ($usage->absence_count ?? 0));
                    $option->setAttribute('user_count', (int) ($usage->user_count ?? 0));

                    return $option;
                }),
            'applicationName' => Setting::valueFor('app_name', config('app.name')),
            'requestLogCount' => AbsenceRequestLog::query()->count(),
        ]);
    }

    public function authentication(Request $request): View
    {
        return view('admin.authentication', [
            ...$this->baseAdminViewData($request),
            'azureConfiguration' => app(AzureAuthenticationService::class)->maskedConfiguration(),
        ]);
    }

    public function users(Request $request): View
    {
        $users = User::query()
            ->select(['id', 'department_id', 'manager_id', 'name', 'email', 'azure_oid', 'password', 'location', 'theme_preference', 'is_admin', 'is_active'])
            ->with(['department:id,name', 'manager:id,name'])
            ->orderBy('name')
            ->get();

        return view('admin.users', [
            ...$this->baseAdminViewData($request),
            'users' => $users,
            'activeUserCount' => $users->where('is_active', true)->count(),
            'adminCount' => $users->where('is_admin', true)->count(),
            'departmentOptions' => Department::query()->orderBy('name')->pluck('name'),
        ]);
    }

    public function logs(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $action = trim((string) $request->input('action', ''));
        $actionOptions = AbsenceRequestLog::actionOptions();

        $logs = AbsenceRequestLog::query()
            ->with(['user.department', 'actor.department'])
            ->when(isset($actionOptions[$action]), function ($query) use ($action) {
                $query->where('action', $action);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('request_uuid', 'like', "%{$search}%")
                        ->orWhere('reason', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('actor', function ($actorQuery) use ($search) {
                            $actorQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.logs', [
            'action' => $action,
            'actionOptions' => $actionOptions,
            'layoutCurrentUser' => $this->currentUserFromSession($request),
            'logs' => $logs,
            'search' => $search,
        ]);
    }

    public function updateApplicationName(Request $request): RedirectResponse
    {
        $request->merge([
            'app_name' => trim((string) $request->input('app_name')),
        ]);

        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:80'],
        ]);

        Setting::query()->updateOrCreate(
            ['key' => 'app_name'],
            ['value' => trim($data['app_name'])]
        );

        return redirect()
            ->route('admin.settings')
            ->with('status', 'Application name updated.');
    }

    public function updateAzureConfiguration(Request $request, AzureAuthenticationService $azureAuthentication): RedirectResponse
    {
        $fieldOptions = array_keys(AzureAuthenticationService::profileFieldOptions());

        $data = $request->validate([
            'tenant_id' => ['required', 'string', 'uuid'],
            'client_id' => ['required', 'string', 'uuid'],
            'client_secret' => ['required', 'string', 'min:16', 'max:255'],
            'department_field' => ['nullable', 'string', Rule::in($fieldOptions)],
            'site_field' => ['nullable', 'string', Rule::in($fieldOptions)],
        ]);

        try {
            $azureAuthentication->storeConfiguration(
                trim($data['tenant_id']),
                trim($data['client_id']),
                trim($data['client_secret']),
                $data['department_field'] ?? null,
                $data['site_field'] ?? null,
            );
        } catch (ValidationException $exception) {
            return back()
                ->withErrors($exception->errors())
                ->withInput($request->only(['tenant_id', 'client_id', 'department_field', 'site_field']));
        }

        return redirect()
            ->route('admin.authentication')
            ->with('status', 'Azure authentication updated. Tenant sign-in endpoints were verified.');
    }

    public function updateUserActivity(Request $request, User $user): RedirectResponse
    {
        $shouldActivate = ! $user->is_active;
        $actorId = $request->session()->get('current_user_id');

        if (! $shouldActivate && User::query()->active()->count() <= 1) {
            return redirect()
                ->route('admin.users')
                ->withErrors(['user_activity' => 'At least one active user must remain available.']);
        }

        DB::transaction(function () use ($actorId, $shouldActivate, $user) {
            $previousIsActive = (bool) $user->is_active;

            $user->update([
                'is_active' => $shouldActivate,
            ]);

            AbsenceRequestLog::query()->create([
                'request_uuid' => null,
                'user_id' => $user->id,
                'actor_id' => is_numeric($actorId) ? (int) $actorId : null,
                'action' => $shouldActivate
                    ? AbsenceRequestLog::ACTION_USER_REACTIVATED
                    : AbsenceRequestLog::ACTION_USER_INACTIVATED,
                'absence_type' => null,
                'status' => $shouldActivate ? 'active' : 'inactive',
                'date_start' => null,
                'date_end' => null,
                'date_count' => 0,
                'reason' => $shouldActivate
                    ? 'User reactivated from the admin panel.'
                    : 'User marked inactive from the admin panel.',
                'metadata' => [
                    'source' => 'admin_user_management',
                    'before' => ['is_active' => $previousIsActive],
                    'after' => ['is_active' => $shouldActivate],
                ],
            ]);
        });

        if (! $shouldActivate && (int) $request->session()->get('current_user_id') === $user->id) {
            $request->session()->forget('current_user_id');
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('home')
                ->with('status', 'Your account was marked inactive and your session was signed out.');
        }

        return redirect()
            ->route('admin.users')
            ->with('status', $shouldActivate
                ? sprintf('%s was reactivated.', $user->name)
                : sprintf('%s was marked inactive.', $user->name));
    }

    public function updateUserAdmin(Request $request, User $user): RedirectResponse
    {
        $shouldGrantAdmin = ! $user->is_admin;

        if (! $shouldGrantAdmin && User::query()->admins()->count() <= 1) {
            return redirect()
                ->route('admin.users')
                ->withErrors(['user_admin' => 'At least one admin must remain available.']);
        }

        $user->update([
            'is_admin' => $shouldGrantAdmin,
        ]);

        return redirect()
            ->route('admin.users')
            ->with('status', $shouldGrantAdmin
                ? sprintf('%s can now manage the admin workspace.', $user->name)
                : sprintf('%s no longer has admin access.', $user->name));
    }

    public function updateUserManager(Request $request, User $user): RedirectResponse
    {
        if (! $user->isManualAccount()) {
            return redirect()
                ->route('admin.users')
                ->withErrors(['user_manager' => 'Only manually created users can have their manager assigned here.']);
        }

        $data = $request->validate([
            'manager_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ]);

        $managerId = isset($data['manager_id']) && $data['manager_id'] !== ''
            ? (int) $data['manager_id']
            : null;

        if ($managerId === $user->id) {
            return redirect()
                ->route('admin.users')
                ->withErrors(['user_manager' => 'A user cannot be their own manager.']);
        }

        $user->update([
            'manager_id' => $managerId,
        ]);

        if ($managerId === null) {
            return redirect()
                ->route('admin.users')
                ->with('status', sprintf('Manager cleared for %s.', $user->name));
        }

        $managerName = User::query()->whereKey($managerId)->value('name') ?? 'the selected manager';

        return redirect()
            ->route('admin.users')
            ->with('status', sprintf('%s now reports to %s.', $user->name, $managerName));
    }

    public function storeManualUser(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'max:255', 'confirmed', Password::min(12)],
            'department_name' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:100'],
            'manager_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'is_admin' => ['nullable', 'boolean'],
        ]);

        $managerId = isset($validated['manager_id']) && $validated['manager_id'] !== ''
            ? (int) $validated['manager_id']
            : null;

        $department = Department::query()->firstOrCreate([
            'name' => trim($validated['department_name'] ?: 'Unassigned'),
        ]);

        $user = User::query()->create([
            'department_id' => $department->id,
            'manager_id' => $managerId,
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'email' => mb_strtolower(trim($validated['email'])),
            'password' => $validated['password'],
            'location' => trim($validated['location'] ?: 'Unassigned'),
            'holiday_country' => 'SE',
            'theme_preference' => User::THEME_LIGHT,
            'is_admin' => (bool) ($validated['is_admin'] ?? false),
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.users')
            ->with('status', sprintf('%s can now sign in with email and password.', $user->name));
    }

    public function storeAbsenceOption(Request $request): RedirectResponse
    {
        $request->merge([
            'code' => Str::upper(trim((string) $request->input('code'))),
        ]);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:10', 'alpha_dash', Rule::unique('absence_options', 'code')],
            'label' => ['required', 'string', 'max:100'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        AbsenceOption::query()->create([
            'code' => $data['code'],
            'label' => $data['label'],
            'color' => $data['color'],
            'sort_order' => (int) AbsenceOption::query()->max('sort_order') + 1,
        ]);

        return redirect()
            ->route('admin.settings')
            ->with('status', sprintf('Absence option %s was added.', $data['label']));
    }

    public function updateAbsenceOption(Request $request, AbsenceOption $absenceOption): RedirectResponse
    {
        $request->merge([
            'code' => Str::upper(trim((string) $request->input('code'))),
        ]);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:10', 'alpha_dash', Rule::unique('absence_options', 'code')->ignore($absenceOption->id)],
            'label' => ['required', 'string', 'max:100'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $originalCode = $absenceOption->code;
        $usage = $this->absenceOptionUsageSummary($originalCode);

        DB::transaction(function () use ($absenceOption, $data, $originalCode) {
            if ($data['code'] !== $originalCode) {
                Absence::query()
                    ->where('type', $originalCode)
                    ->update(['type' => $data['code']]);
            }

            $absenceOption->update([
                'code' => $data['code'],
                'label' => $data['label'],
                'color' => $data['color'],
            ]);
        });

        $status = sprintf('Absence option %s was updated.', $data['label']);

        if ($usage['absence_count'] > 0) {
            $status .= sprintf(
                ' Warning acknowledged: %d day(s) from %d people already used this option.%s',
                $usage['absence_count'],
                $usage['user_count'],
                $data['code'] !== $originalCode ? ' Existing days were moved to the new code.' : ''
            );
        }

        return redirect()
            ->route('admin.settings')
            ->with('status', $status);
    }

    public function destroyAbsenceOption(AbsenceOption $absenceOption): RedirectResponse
    {
        $usage = $this->absenceOptionUsageSummary($absenceOption->code);
        $label = $absenceOption->label;

        $absenceOption->delete();

        $status = sprintf('Absence option %s was deleted.', $label);

        if ($usage['absence_count'] > 0) {
            $status .= sprintf(
                ' Warning acknowledged: %d day(s) from %d people still reference the deleted code.',
                $usage['absence_count'],
                $usage['user_count']
            );
        }

        return redirect()
            ->route('admin.settings')
            ->with('status', $status);
    }

    /**
     * @return array{currentUser:?User,layoutCurrentUser:?User}
     */
    private function baseAdminViewData(Request $request): array
    {
        $currentUser = $this->currentUserFromSession($request);

        return [
            'currentUser' => $currentUser,
            'layoutCurrentUser' => $currentUser,
        ];
    }

    private function absenceOptionUsageByCode(): Collection
    {
        return Absence::query()
            ->selectRaw('type, COUNT(*) as absence_count, COUNT(DISTINCT user_id) as user_count')
            ->groupBy('type')
            ->get()
            ->keyBy('type');
    }

    private function currentUserFromSession(Request $request): ?User
    {
        $currentUserId = $request->session()->get('current_user_id');

        if ($currentUserId === null) {
            return null;
        }

        return User::query()
            ->active()
            ->select(['id', 'department_id', 'manager_id', 'name', 'email', 'location', 'holiday_country', 'theme_preference', 'is_admin'])
            ->with(['department:id,name', 'manager:id,name'])
            ->find($currentUserId);
    }

    /**
     * @return array{absence_count:int,user_count:int}
     */
    private function absenceOptionUsageSummary(string $code): array
    {
        $usage = Absence::query()
            ->where('type', $code)
            ->selectRaw('COUNT(*) as absence_count, COUNT(DISTINCT user_id) as user_count')
            ->first();

        return [
            'absence_count' => (int) ($usage->absence_count ?? 0),
            'user_count' => (int) ($usage->user_count ?? 0),
        ];
    }
}
