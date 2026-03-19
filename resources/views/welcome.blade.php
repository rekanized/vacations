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
            <section class="guest-card guest-card-hero guest-card-landing">
                <div class="guest-brand">
                    <img src="{{ asset('brand/leaveboard-mark.svg') }}" alt="{{ config('app.name', 'LeaveBoard') }} logo">
                    <div>
                        <p class="guest-kicker">Leave planning</p>
                        <h1 class="guest-title">{{ config('app.name', 'LeaveBoard') }}</h1>
                    </div>
                </div>

                <p class="guest-copy guest-copy-landing">
                    A clear shared view of team leave, approvals, and upcoming time away.
                </p>

                @if (session('status'))
                    <div class="app-status">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <ul class="error-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif

                <div class="guest-info-strip" aria-label="Application highlights">
                    <span class="guest-info-pill">Multi-month planner</span>
                    <span class="guest-info-pill">Manager approvals</span>
                    <span class="guest-info-pill">Country-aware holidays</span>
                </div>

                <div class="guest-actions">
                    @if ($azureConfigured)
                        <a href="{{ route('login') }}" class="admin-button">Sign in with Microsoft</a>
                    @else
                        <span class="admin-button secondary" aria-disabled="true">Microsoft sign-in unavailable</span>
                    @endif

                    <a href="{{ route('login.manual.form') }}" class="admin-button secondary">Manual sign-in</a>
                </div>

                <p class="guest-copy guest-copy-note">
                    Use Microsoft sign-in for the standard flow. Manual sign-in is available for locally managed accounts and remains reachable from here.
                </p>
            </section>
        </main>
    </body>
</html>