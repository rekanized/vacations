<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ManualAuthenticationController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.manual-login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $email = mb_strtolower(trim($validated['email']));
        $rateLimitKey = $this->rateLimitKey($request, $email);

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            throw ValidationException::withMessages([
                'manual_auth' => sprintf('Too many sign-in attempts. Try again in %d seconds.', max($seconds, 1)),
            ]);
        }

        $user = User::query()
            ->where('email', $email)
            ->first();

        if ($user === null || ! filled($user->password) || ! Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($rateLimitKey, 60);

            throw ValidationException::withMessages([
                'manual_auth' => 'The provided email or password is incorrect.',
            ]);
        }

        if (! $user->is_active) {
            RateLimiter::hit($rateLimitKey, 60);

            throw ValidationException::withMessages([
                'manual_auth' => 'Your account is inactive. Contact an administrator.',
            ]);
        }

        RateLimiter::clear($rateLimitKey);
        $request->session()->regenerate();
        $request->session()->put('current_user_id', $user->id);

        return redirect()->intended(route('planner'));
    }

    private function rateLimitKey(Request $request, string $email): string
    {
        return Str::transliterate(sprintf('manual-login|%s|%s', $email, (string) $request->ip()));
    }
}