<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? (config('app.name', 'LeaveBoard') . ' · Planner') }}</title>

        <link rel="icon" type="image/svg+xml" href="{{ asset('brand/leaveboard-mark.svg') }}">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
        <link rel="stylesheet" href="{{ asset('app.css') }}">

        @livewireStyles
    </head>
    @php
        $applicationName = config('app.name', 'LeaveBoard');
        $layoutCurrentUser = $layoutCurrentUser ?? $currentUser ?? null;
        $layoutInitials = $layoutCurrentUser
            ? collect(explode(' ', $layoutCurrentUser->name))
                ->filter()
                ->take(2)
                ->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))
                ->implode('')
            : (collect(explode(' ', $applicationName))
                ->filter()
                ->take(2)
                ->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))
                ->implode('') ?: 'LB');
        $layoutTheme = $layoutCurrentUser?->theme_preference === \App\Models\User::THEME_DARK
            ? \App\Models\User::THEME_DARK
            : \App\Models\User::THEME_LIGHT;
    @endphp
    <body class="app-body" data-theme="{{ $layoutTheme }}">
        <div class="app-shell">
            <aside class="app-sidebar">
                <a href="{{ route('planner') }}" class="app-brand">
                    <img src="{{ asset('brand/leaveboard-mark.svg') }}" alt="{{ $applicationName }} logo">
                    <span class="app-brand-copy">
                        <span class="app-brand-kicker">Workspace</span>
                        <span class="app-brand-name">{{ $applicationName }}</span>
                        <span class="app-brand-subtitle">Plan leave with clarity</span>
                    </span>
                </a>

                <section class="sidebar-section">
                    <p class="sidebar-section-title">Navigation</p>

                    <nav class="sidebar-nav">
                        <a href="{{ route('planner') }}" class="sidebar-link {{ request()->routeIs('planner') ? 'active' : '' }}">
                            <span class="sidebar-link-icon">
                                <span class="icon">calendar_month</span>
                            </span>
                            <span class="sidebar-link-copy">
                                <span class="sidebar-link-title">Planner</span>
                                <span class="sidebar-link-meta">Team availability and leave</span>
                            </span>
                        </a>

                        <a href="{{ route('profile.show') }}" class="sidebar-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                            <span class="sidebar-link-icon">
                                <span class="icon">person</span>
                            </span>
                            <span class="sidebar-link-copy">
                                <span class="sidebar-link-title">Profile</span>
                                <span class="sidebar-link-meta">Holiday country and request history</span>
                            </span>
                        </a>

                        @if ($layoutCurrentUser?->is_admin)
                            <details class="sidebar-nav-group" @if (request()->routeIs('admin.*')) open @endif>
                                <summary class="sidebar-link sidebar-link-group {{ request()->routeIs('admin.*') ? 'active' : '' }}">
                                    <span class="sidebar-link-icon">
                                        <span class="icon">tune</span>
                                    </span>
                                    <span class="sidebar-link-copy">
                                        <span class="sidebar-link-title">Admin</span>
                                        <span class="sidebar-link-meta">Authentication, users, and application setup</span>
                                    </span>
                                    <span class="sidebar-link-expand icon">expand_more</span>
                                </summary>

                                <div class="sidebar-subnav">
                                    <a href="{{ route('admin.authentication') }}" class="sidebar-sublink {{ request()->routeIs('admin.authentication') ? 'active' : '' }}">Authentication</a>
                                    <a href="{{ route('admin.users') }}" class="sidebar-sublink {{ request()->routeIs('admin.users') ? 'active' : '' }}">User information</a>
                                    <a href="{{ route('admin.settings') }}" class="sidebar-sublink {{ request()->routeIs('admin.settings') || request()->routeIs('admin.index') ? 'active' : '' }}">Application settings</a>
                                    <a href="{{ route('admin.logs') }}" class="sidebar-sublink {{ request()->routeIs('admin.logs') ? 'active' : '' }}">Request log</a>
                                </div>
                            </details>
                        @endif
                    </nav>

                    @if ($layoutCurrentUser)
                        <x-theme-toggle-form
                            :theme="$layoutTheme"
                            heading="Appearance"
                            copy="Saved to your profile settings."
                            class="theme-toggle-form-sidebar"
                            button-class="theme-toggle-button-sidebar"
                        />
                    @endif
                </section>

                <div class="sidebar-spacer"></div>

                @if ($layoutCurrentUser)
                    <div class="sidebar-user">
                        <span class="sidebar-user-avatar">{{ $layoutInitials }}</span>
                        <span class="sidebar-user-copy">
                            <span class="sidebar-user-label">Signed in user</span>
                            <span class="sidebar-user-name">{{ $layoutCurrentUser->name }}</span>
                            <span class="sidebar-user-meta">{{ $layoutCurrentUser->department?->name ?? 'No department set' }}</span>
                            @if ($layoutCurrentUser->email)
                                <span class="sidebar-user-meta">{{ $layoutCurrentUser->email }}</span>
                            @endif
                            @if ($layoutCurrentUser->manager)
                                <span class="sidebar-user-meta">Manager: {{ $layoutCurrentUser->manager->name }}</span>
                            @endif
                            @if ($layoutCurrentUser->is_admin)
                                <span class="sidebar-user-meta">Role: Admin</span>
                            @endif
                        </span>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="sidebar-footer-link sidebar-footer-button">
                            <span class="icon">logout</span>
                            <span>Sign out</span>
                        </button>
                    </form>
                @endif

                <a href="https://github.com/rekanized/vacations" class="sidebar-footer-link" target="_blank" rel="noopener noreferrer">
                    <span class="icon">code</span>
                    <span>GitHub repository</span>
                </a>
            </aside>

            <div class="app-content">
                @if (session('status'))
                    <div class="app-status-wrap">
                        <div class="app-status">{{ session('status') }}</div>
                    </div>
                @endif

                <main class="app-main">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
