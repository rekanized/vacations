<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'LeaveBoard') }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('brand/leaveboard-mark.svg') }}">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('app.css') }}">
    </head>
    <body class="app-body" data-theme="light">
        <main class="guest-shell guest-shell-landing">
            <section class="guest-card guest-card-hero guest-card-landing guest-card-manual-login">
                <div class="guest-brand">
                    <img src="{{ asset('brand/leaveboard-mark.svg') }}" alt="{{ config('app.name', 'LeaveBoard') }} logo">
                    <div>
                        <h1 class="guest-title">Manual sign-in</h1>
                    </div>
                </div>

                @php
                    $manualLoginAvailable = \App\Models\User::query()->whereNotNull('password')->exists();
                @endphp

                <p class="guest-copy guest-copy-note">
                    Sign in with a locally managed email and password account.
                </p>

                @if (! $manualLoginAvailable)
                    <div class="app-status" style="background: var(--status-neutral-bg); border-color: var(--status-neutral-border); color: var(--status-neutral-fg); box-shadow: none;">
                        No manual sign-in accounts are available yet. Ask an admin to create one from the User information page.
                    </div>
                @endif

                @if ($errors->any())
                    <ul class="error-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif

                <form method="POST" action="{{ route('login.manual') }}" class="admin-form guest-manual-form">
                    @csrf

                    <label class="admin-label">
                        Email
                        <input type="email" name="email" class="admin-input" value="{{ old('email') }}" placeholder="name@example.com" @disabled(! $manualLoginAvailable)>
                    </label>

                    <label class="admin-label">
                        Password
                        <input type="password" name="password" class="admin-input" placeholder="Enter your password" @disabled(! $manualLoginAvailable)>
                    </label>

                    <div class="guest-actions guest-actions-stacked">
                        <button type="submit" class="admin-button secondary" @disabled(! $manualLoginAvailable)>Sign in manually</button>
                        <a href="{{ route('home') }}" class="admin-button ghost">Back</a>
                    </div>
                </form>
            </section>
        </main>
    </body>
</html>