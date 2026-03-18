<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? (config('app.name', 'LeaveBoard') . ' · Absence Planner') }}</title>
        
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
        
        <style>
            [x-cloak] { display: none !important; }
            :root {
                --primary-bg: #f8fafc;
                --card-bg: rgba(255, 255, 255, 0.8);
                --text-main: #1e293b;
                --text-muted: #64748b;
                --border-color: #e2e8f0;
                --accent-red: #ef4444;
                --accent-green: #22c55e;
                --accent-blue: #3b82f6;
                --accent-yellow: #eab308;
            }

            body {
                margin: 0;
                padding: 0;
                background:
                    radial-gradient(circle at top left, rgba(59, 130, 246, 0.14), transparent 28%),
                    radial-gradient(circle at bottom right, rgba(14, 165, 233, 0.10), transparent 24%),
                    var(--primary-bg);
                color: var(--text-main);
                font-family: 'Inter', sans-serif;
                -webkit-font-smoothing: antialiased;
            }

            * {
                box-sizing: border-box;
            }

            .icon {
                font-family: 'Material Symbols Outlined';
                font-weight: normal;
                font-style: normal;
                font-size: 24px;
                line-height: 1;
                letter-spacing: normal;
                text-transform: none;
                display: inline-block;
                white-space: nowrap;
                word-wrap: normal;
                direction: ltr;
                -webkit-font-smoothing: antialiased;
            }
            .app-brand {
                display: flex;
                align-items: center;
                gap: 12px;
                text-decoration: none;
                color: var(--text-main);
                font-weight: 800;
                letter-spacing: -0.02em;
            }

            .app-brand img {
                width: 40px;
                height: 40px;
                display: block;
                border-radius: 14px;
                box-shadow: 0 16px 32px rgba(59, 130, 246, 0.18);
            }

            .app-shell {
                min-height: 100vh;
                display: flex;
            }

            .app-sidebar {
                width: 288px;
                padding: 24px 18px;
                position: sticky;
                top: 0;
                height: 100vh;
                display: flex;
                flex-direction: column;
                gap: 24px;
                background: rgba(255, 255, 255, 0.72);
                backdrop-filter: blur(20px);
                border-right: 1px solid rgba(148, 163, 184, 0.18);
                box-shadow: inset -1px 0 0 rgba(255, 255, 255, 0.4);
            }

            .app-brand-copy {
                display: flex;
                flex-direction: column;
                gap: 2px;
                min-width: 0;
            }

            .app-brand-kicker {
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                color: var(--accent-blue);
            }

            .app-brand-name {
                font-size: 18px;
                line-height: 1.1;
            }

            .app-brand-subtitle {
                color: var(--text-muted);
                font-size: 12px;
                font-weight: 600;
            }

            .sidebar-section {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .sidebar-section-title {
                margin: 0;
                padding: 0 10px;
                color: var(--text-muted);
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0.12em;
                text-transform: uppercase;
            }

            .sidebar-nav {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }

            .sidebar-link {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 14px 14px;
                border-radius: 18px;
                text-decoration: none;
                color: var(--text-main);
                transition: transform 0.18s ease, background 0.18s ease, box-shadow 0.18s ease;
            }

            .sidebar-link:hover {
                transform: translateX(2px);
                background: rgba(255, 255, 255, 0.72);
            }

            .sidebar-link.active {
                background: linear-gradient(135deg, rgba(59, 130, 246, 0.18), rgba(14, 165, 233, 0.12));
                box-shadow: 0 16px 30px rgba(59, 130, 246, 0.12);
            }

            .sidebar-link-icon {
                width: 40px;
                height: 40px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 14px;
                background: rgba(255, 255, 255, 0.82);
                color: var(--accent-blue);
                flex-shrink: 0;
            }

            .sidebar-link-copy {
                display: flex;
                flex-direction: column;
                gap: 2px;
                min-width: 0;
            }

            .sidebar-link-title {
                font-size: 14px;
                font-weight: 700;
            }

            .sidebar-link-meta {
                color: var(--text-muted);
                font-size: 12px;
                font-weight: 600;
            }

            .sidebar-note {
                padding: 14px;
                border-radius: 18px;
                background: rgba(15, 23, 42, 0.04);
                border: 1px solid rgba(148, 163, 184, 0.14);
                color: var(--text-muted);
                font-size: 12px;
                line-height: 1.5;
            }

            .sidebar-spacer {
                flex: 1;
            }

            .sidebar-user {
                display: flex;
                align-items: flex-start;
                gap: 12px;
                padding: 16px;
                border-radius: 22px;
                background: rgba(255, 255, 255, 0.86);
                border: 1px solid rgba(148, 163, 184, 0.16);
                box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            }

            .sidebar-user-avatar {
                width: 44px;
                height: 44px;
                border-radius: 16px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg, #2563eb, #0ea5e9);
                color: white;
                font-size: 16px;
                font-weight: 800;
                flex-shrink: 0;
            }

            .sidebar-user-copy {
                display: flex;
                flex-direction: column;
                gap: 4px;
                min-width: 0;
            }

            .sidebar-user-label {
                color: var(--text-muted);
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0.1em;
                text-transform: uppercase;
            }

            .sidebar-user-name {
                font-size: 14px;
                font-weight: 800;
                word-break: break-word;
            }

            .sidebar-user-meta {
                color: var(--text-muted);
                font-size: 12px;
                line-height: 1.4;
            }

            .sidebar-footer-link {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                margin-top: 12px;
                padding: 10px 14px;
                border-radius: 16px;
                color: var(--text-muted);
                text-decoration: none;
                font-size: 12px;
                font-weight: 700;
                transition: background 0.18s ease, color 0.18s ease, transform 0.18s ease;
            }

            .sidebar-footer-link:hover {
                background: rgba(255, 255, 255, 0.72);
                color: var(--text-main);
                transform: translateX(2px);
            }

            .app-content {
                flex: 1;
                min-width: 0;
                display: flex;
                flex-direction: column;
            }

            .app-status-wrap {
                padding: 24px 24px 0;
            }

            .app-status {
                max-width: 1400px;
                margin: 0 auto;
                padding: 14px 18px;
                border-radius: 18px;
                background: rgba(220, 252, 231, 0.78);
                border: 1px solid rgba(34, 197, 94, 0.16);
                color: #166534;
                font-size: 14px;
                font-weight: 700;
                box-shadow: 0 16px 30px rgba(34, 197, 94, 0.08);
            }

            .app-main {
                flex: 1;
                min-width: 0;
            }

            @media (max-width: 1024px) {
                .app-shell {
                    flex-direction: column;
                }

                .app-sidebar {
                    width: auto;
                    height: auto;
                    position: relative;
                    border-right: 0;
                    border-bottom: 1px solid rgba(148, 163, 184, 0.18);
                }

                .sidebar-spacer {
                    display: none;
                }
            }

            @media (max-width: 640px) {
                .app-sidebar,
                .app-status-wrap {
                    padding-left: 16px;
                    padding-right: 16px;
                }

                .sidebar-link {
                    padding: 12px;
                }
            }
        </style>

        @livewireStyles
    </head>
    <body class="bg-slate-50">
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
        @endphp

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

                        <a href="{{ route('admin.index') }}" class="sidebar-link {{ request()->routeIs('admin.*') ? 'active' : '' }}">
                            <span class="sidebar-link-icon">
                                <span class="icon">tune</span>
                            </span>
                            <span class="sidebar-link-copy">
                                <span class="sidebar-link-title">Admin</span>
                                <span class="sidebar-link-meta">Impersonation and setup tools</span>
                            </span>
                        </a>
                    </nav>
                </section>

                <div class="sidebar-spacer"></div>

                @if ($layoutCurrentUser)
                    <div class="sidebar-user">
                        <span class="sidebar-user-avatar">{{ $layoutInitials }}</span>
                        <span class="sidebar-user-copy">
                            <span class="sidebar-user-label">Current user</span>
                            <span class="sidebar-user-name">{{ $layoutCurrentUser->name }}</span>
                            <span class="sidebar-user-meta">
                                {{ $layoutCurrentUser->department?->name ?? 'No department set' }}
                                @if ($layoutCurrentUser->manager)
                                    <br>Manager: {{ $layoutCurrentUser->manager->name }}
                                @endif
                            </span>
                        </span>
                    </div>
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
