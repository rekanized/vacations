<?php

namespace App\Http\Controllers;

use App\Models\Absence;
use App\Models\AbsenceRequestLog;
use App\Models\AbsenceOption;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(Request $request): View
    {
        $absenceOptionUsage = $this->absenceOptionUsageByCode();
        $currentUser = $this->currentUserFromSession($request);

        return view('admin.index', [
            'currentUser' => $currentUser,
            'layoutCurrentUser' => $currentUser,
            'users' => User::query()
                ->select(['id', 'department_id', 'manager_id', 'name', 'location'])
                ->with(['department:id,name', 'manager:id,name'])
                ->orderBy('name')
                ->get(),
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
            ->route('admin.index')
            ->with('status', 'Application name updated.');
    }

    public function impersonate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')],
        ]);

        $request->session()->put('current_user_id', (int) $data['user_id']);

        $name = User::query()->whereKey($data['user_id'])->value('name');

        return redirect()
            ->route('admin.index')
            ->with('status', sprintf('You are now impersonating %s.', $name));
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
            ->route('admin.index')
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
            ->route('admin.index')
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
            ->route('admin.index')
            ->with('status', $status);
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
            ->select(['id', 'department_id', 'manager_id', 'name', 'location'])
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
