<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use App\Support\AzureAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SetupController extends Controller
{
    public function show(AzureAuthenticationService $azureAuthentication): View|RedirectResponse
    {
        if (User::query()->exists()) {
            return redirect()->route('home');
        }

        return view('auth.setup', [
            'azureConfiguration' => $azureAuthentication->maskedConfiguration(),
        ]);
    }

    public function store(Request $request, AzureAuthenticationService $azureAuthentication): RedirectResponse
    {
        abort_if(User::query()->exists(), 403);

        $validated = $request->validate([
            'tenant_id' => ['required', 'string', 'uuid'],
            'client_id' => ['required', 'string', 'uuid'],
            'client_secret' => ['required', 'string', 'min:16', 'max:255'],
        ]);

        try {
            $azureAuthentication->storeConfiguration(
                trim($validated['tenant_id']),
                trim($validated['client_id']),
                trim($validated['client_secret']),
            );
        } catch (ValidationException $exception) {
            return back()
                ->withErrors($exception->errors())
                ->withInput($request->only(['tenant_id', 'client_id']));
        }

        return redirect()
            ->route('setup.show')
            ->with('status', 'Azure authentication is configured. Tenant sign-in endpoints were verified. Continue with Microsoft sign-in to finish setup.');
    }

    public function storeManualAdmin(Request $request): RedirectResponse
    {
        abort_if(User::query()->exists(), 403);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'max:255', 'confirmed', Password::min(12)],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $department = Department::query()->firstOrCreate([
                'name' => 'Unassigned',
            ]);

            return User::query()->create([
                'department_id' => $department->id,
                'manager_id' => null,
                'name' => trim($validated['first_name'].' '.$validated['last_name']),
                'email' => mb_strtolower(trim($validated['email'])),
                'password' => $validated['password'],
                'location' => 'Unassigned',
                'holiday_country' => 'SE',
                'theme_preference' => User::THEME_LIGHT,
                'is_admin' => true,
                'is_active' => true,
            ]);
        });

        $request->session()->regenerate();
        $request->session()->put('current_user_id', $user->id);

        return redirect()
            ->route('planner')
            ->with('status', 'Manual admin account created. Azure can be configured later from the admin workspace.');
    }
}