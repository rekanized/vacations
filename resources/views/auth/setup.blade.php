<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'LeaveBoard') }} · Setup</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('brand/leaveboard-mark.svg') }}">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('app.css') }}">
    </head>
    <body class="app-body" data-theme="light">
        <main class="guest-shell guest-shell-setup">
            <section class="guest-card guest-card-hero guest-card-setup-hero">
                <div class="guest-brand">
                    <img src="{{ asset('brand/leaveboard-mark.svg') }}" alt="{{ config('app.name', 'LeaveBoard') }} logo">
                    <div>
                        <p class="planner-kicker">First-run setup</p>
                        <h1 class="guest-title">Choose how LeaveBoard signs users in</h1>
                    </div>
                </div>

                <p class="guest-copy guest-copy-hero">
                    Start with Azure for the cleanest tenant-backed setup, or create one manual admin and add Azure later from the admin workspace.
                </p>

                <div class="setup-hero-points">
                    <div class="setup-hero-point">
                        <span class="guest-badge">Recommended</span>
                        <span>Azure tenant sign-in with automatic first-user bootstrap</span>
                    </div>
                    <div class="setup-hero-point">
                        <span class="setup-hero-dot"></span>
                        <span>Manual admin fallback when Azure is not ready yet</span>
                    </div>
                    <div class="setup-hero-point">
                        <span class="setup-hero-dot"></span>
                        <span>Azure can still be configured later without rebuilding the app</span>
                    </div>
                </div>

                @if (session('status'))
                    <div class="app-status">{{ session('status') }}</div>
                @endif

                @if ($azureConfiguration['configured'])
                    <div class="guest-actions">
                        <a href="{{ route('login') }}" class="admin-button">Sign in with Microsoft</a>
                        <span class="guest-copy setup-inline-copy">Azure is ready. Continue directly to Microsoft sign-in.</span>
                    </div>
                @endif

                @if ($errors->any())
                    <ul class="error-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="setup-layout">
                <article class="guest-card setup-card-primary">
                    <div class="guest-section-head">
                        <div>
                            <p class="planner-kicker">Recommended path</p>
                            <h2>Connect Azure</h2>
                        </div>
                        <span class="guest-badge">Azure</span>
                    </div>

                    <p class="guest-copy">Enter the application credentials from Azure App Registration and verify the Microsoft sign-in endpoints.</p>

                    <form method="POST" action="{{ route('setup.store') }}" class="admin-form setup-form-grid">
                        @csrf

                        <label class="admin-label setup-form-span-2">
                            Tenant ID
                            <input name="tenant_id" class="admin-input" value="{{ old('tenant_id', $azureConfiguration['tenant_id'] ?? '') }}" placeholder="00000000-0000-0000-0000-000000000000">
                        </label>

                        <label class="admin-label setup-form-span-2">
                            Client ID
                            <input name="client_id" class="admin-input" value="{{ old('client_id', $azureConfiguration['client_id'] ?? '') }}" placeholder="00000000-0000-0000-0000-000000000000">
                        </label>

                        <label class="admin-label setup-form-span-2">
                            Client secret
                            <input type="password" name="client_secret" class="admin-input" placeholder="Paste the Azure app registration secret">
                        </label>

                        <div class="setup-inline-note setup-form-span-2">
                            <span class="setup-inline-label">Redirect URI</span>
                            <p class="guest-mono">{{ $azureConfiguration['redirect_uri'] }}</p>
                        </div>

                        <div class="setup-checklist setup-form-span-2">
                            <div class="setup-checklist-item">Grant delegated Microsoft Graph access for user profile and manager lookup.</div>
                            <div class="setup-checklist-item">After saving, return to the landing page and sign in with the first admin account.</div>
                        </div>

                        <section class="setup-permissions-panel setup-form-span-2" aria-label="Azure permissions summary">
                            <div class="setup-permissions-panel-head">
                                <div>
                                    <span class="setup-inline-label">Azure requirements</span>
                                    <h3 class="setup-permissions-title">App registration checklist</h3>
                                </div>
                                <span class="setup-permissions-status">Required</span>
                            </div>

                            <div class="setup-permission-specs">
                                <div class="setup-permission-spec">
                                    <span class="setup-permission-label">Platform</span>
                                    <div class="setup-permission-value">
                                        <div class="setup-token-row">
                                            <span class="setup-token">Web</span>
                                        </div>
                                        <p>Use the redirect URI shown above exactly.</p>
                                    </div>
                                </div>

                                <div class="setup-permission-spec">
                                    <span class="setup-permission-label">Microsoft Graph</span>
                                    <div class="setup-permission-value">
                                        <div class="setup-token-row">
                                            <span class="setup-token">User.Read</span>
                                            <span class="setup-token">User.Read.All</span>
                                        </div>
                                        <p>User.Read handles the signed-in profile. User.Read.All is needed for manager and synced organization fields.</p>
                                    </div>
                                </div>

                                <div class="setup-permission-spec">
                                    <span class="setup-permission-label">OpenID scopes</span>
                                    <div class="setup-permission-value">
                                        <div class="setup-token-row">
                                            <span class="setup-token">openid</span>
                                            <span class="setup-token">profile</span>
                                            <span class="setup-token">email</span>
                                            <span class="setup-token">offline_access</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="setup-permission-spec">
                                    <span class="setup-permission-label">Tenant consent</span>
                                    <div class="setup-permission-value">
                                        <p>Grant admin consent after adding the delegated permissions, or profile and manager syncing can fail even if sign-in succeeds.</p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <div class="setup-action-row setup-form-span-2">
                            <button type="submit" class="admin-button">Save and verify Azure</button>

                            @if ($azureConfiguration['configured'])
                                <a href="{{ route('login') }}" class="admin-button secondary">Sign in with Microsoft</a>
                            @endif
                        </div>
                    </form>
                </article>

                <div class="setup-side-stack">
                    <article class="guest-card">
                        <div class="guest-section-head">
                            <div>
                                <p class="planner-kicker">Fallback path</p>
                                <h2>Create manual admin</h2>
                            </div>
                            <span class="setup-side-tag">Optional</span>
                        </div>

                        <p class="guest-copy">Use this only when you need to start without Azure. You can still connect Azure later from Admin.</p>

                        <form method="POST" action="{{ route('setup.manual-admin.store') }}" class="admin-form">
                            @csrf

                            <div class="setup-two-up">
                                <label class="admin-label">
                                    Name
                                    <input name="first_name" class="admin-input" value="{{ old('first_name') }}" placeholder="Asta">
                                </label>

                                <label class="admin-label">
                                    Lastname
                                    <input name="last_name" class="admin-input" value="{{ old('last_name') }}" placeholder="Admin">
                                </label>
                            </div>

                            <label class="admin-label">
                                Email
                                <input type="email" name="email" class="admin-input" value="{{ old('email') }}" placeholder="asta@example.com">
                            </label>

                            <label class="admin-label">
                                Password
                                <input type="password" name="password" class="admin-input" placeholder="At least 12 characters">
                            </label>

                            <label class="admin-label">
                                Confirm password
                                <input type="password" name="password_confirmation" class="admin-input" placeholder="Repeat the password">
                            </label>

                            <button type="submit" class="admin-button secondary">Create manual admin</button>
                        </form>
                    </article>

                    <article class="guest-card setup-card-note">
                        <p class="planner-kicker">Before you start</p>
                        <div class="setup-mini-list">
                            <div class="setup-mini-item">Use Azure when you want tenant-backed identity from day one.</div>
                            <div class="setup-mini-item">Use manual startup when you need access before Azure is approved or ready.</div>
                            <div class="setup-mini-item">Both paths use the same planner, profile, admin, and logout flow afterwards.</div>
                            <div class="setup-mini-item">Azure sign-in also expects Microsoft Graph delegated permissions and tenant admin consent.</div>
                        </div>
                    </article>
                </div>
            </section>
        </main>
    </body>
</html>