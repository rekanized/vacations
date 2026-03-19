<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use App\Support\AzureAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AzureAuthenticationController extends Controller
{
    public function redirectToProvider(Request $request, AzureAuthenticationService $azureAuthentication): RedirectResponse
    {
        abort_unless($azureAuthentication->hasConfiguration(), 404);

        $state = Str::random(40);
        $nonce = Str::random(40);

        $request->session()->put('azure_auth_state', $state);
        $request->session()->put('azure_auth_nonce', $nonce);

        return redirect()->away($azureAuthentication->authorizationUrl($state, $nonce));
    }

    public function handleCallback(Request $request, AzureAuthenticationService $azureAuthentication): RedirectResponse
    {
        $request->validate([
            'state' => ['required', 'string'],
            'code' => ['nullable', 'string'],
            'error' => ['nullable', 'string'],
            'error_description' => ['nullable', 'string'],
        ]);

        $expectedState = (string) $request->session()->pull('azure_auth_state', '');
        $expectedNonce = (string) $request->session()->pull('azure_auth_nonce', '');
        $returnedState = (string) $request->input('state', '');

        if ($expectedState === '' || ! hash_equals($expectedState, $returnedState)) {
            throw ValidationException::withMessages([
                'azure_auth' => 'The Microsoft sign-in session expired. Please try again.',
            ]);
        }

        if ($request->filled('error')) {
            throw ValidationException::withMessages([
                'azure_auth' => trim((string) $request->input('error_description', 'Microsoft sign-in was cancelled.')),
            ]);
        }

        $identity = $azureAuthentication->resolveIdentityFromAuthorizationCode(
            (string) $request->input('code', ''),
            $expectedNonce,
        );
        $user = $this->resolveUserFromAzureIdentity($identity);

        if (! $user->is_active) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('home')
                ->withErrors(['azure_auth' => 'Your account is inactive. Contact an administrator.']);
        }

        $request->session()->regenerate();
        $request->session()->put('current_user_id', $user->id);

        return redirect()->intended(route('planner'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['current_user_id', 'azure_auth_nonce', 'azure_auth_state']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'You have been signed out.');
    }

    private function resolveUserFromAzureIdentity(array $identity): User
    {
        return DB::transaction(function () use ($identity) {
            $department = Department::query()->firstOrCreate([
                'name' => trim((string) ($identity['department_name'] ?? 'Unassigned')) ?: 'Unassigned',
            ]);

            $existingUser = User::query()
                ->when($identity['azure_oid'] !== null, function ($query) use ($identity) {
                    $query->where('azure_oid', $identity['azure_oid']);
                })
                ->when($identity['email'] !== null, function ($query) use ($identity) {
                    $query->orWhere('email', $identity['email']);
                })
                ->orderBy('id')
                ->first();

            if ($existingUser !== null) {
                $existingUser->update([
                    'department_id' => $department->id,
                    'name' => $identity['name'],
                    'email' => $identity['email'],
                    'azure_oid' => $identity['azure_oid'],
                    'location' => trim((string) ($identity['site_name'] ?? 'Unassigned')) ?: 'Unassigned',
                ]);

                return $existingUser->refresh();
            }

            return User::query()->create([
                'department_id' => $department->id,
                'manager_id' => null,
                'name' => $identity['name'],
                'email' => $identity['email'],
                'azure_oid' => $identity['azure_oid'],
                'password' => null,
                'location' => trim((string) ($identity['site_name'] ?? 'Unassigned')) ?: 'Unassigned',
                'holiday_country' => 'SE',
                'theme_preference' => User::THEME_LIGHT,
                'is_admin' => User::query()->count() === 0,
                'is_active' => true,
            ]);
        });
    }
}