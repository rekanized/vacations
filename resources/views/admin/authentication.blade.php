<x-layouts.app :layout-current-user="$layoutCurrentUser">
<div class="page-shell page-shell-fluid">
    <section class="admin-card admin-stack">
        <div>
            <p class="planner-kicker">Admin workspace</p>
            <h1 style="font-size: 28px; margin-bottom: 12px;">Authentication</h1>
            <p style="color: var(--text-soft); max-width: 760px; margin: 0;">
                Configure Microsoft sign-in separately from the rest of the admin tooling so longer credentials, redirect details, and permission guidance have room to breathe.
            </p>
        </div>

        @if ($errors->any())
            <ul class="error-list">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        @if ($currentUser)
            <div class="admin-chip">
                Signed in as {{ $currentUser->name }}
                <span style="color: var(--text-soft); font-weight: 600;">· {{ $currentUser->is_admin ? 'Admin' : 'Standard user' }}</span>
            </div>
        @endif
    </section>

    <section class="admin-card admin-azure-section">
        <div class="admin-azure-header">
            <div>
                <p class="planner-kicker">Identity setup</p>
                <h2>Azure authentication</h2>
                <p style="color: var(--text-soft); max-width: 68ch; margin-bottom: 0;">These values control Microsoft sign-in, first-user bootstrap, and the profile sync that feeds departments, manager data, and organization details.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.azure-auth.update') }}" class="admin-form admin-azure-panel">
            @csrf

            <div class="admin-azure-panel-head">
                <div>
                    <span class="setup-inline-label">Connection settings</span>
                    <h3 class="setup-permissions-title">Update and verify Azure</h3>
                    <p class="admin-helper-text">Tenant ID and Client ID can be reviewed and updated here. The client secret is never shown back in full and must be entered when you want to verify or replace the stored configuration.</p>
                </div>

                <div class="admin-chip {{ $azureConfiguration['configured'] ? 'success' : 'warning' }}">
                    {{ $azureConfiguration['configured'] ? 'Configured' : 'Not configured' }}
                </div>
            </div>

            <div class="admin-azure-panel-body">
                <section class="admin-azure-panel-section admin-azure-panel-section-info" aria-label="Azure application registration details">
                    <div>
                        <span class="setup-inline-label">Registration requirements</span>
                        <h4 class="admin-azure-section-title">What Azure must match</h4>
                    </div>

                    <div class="admin-azure-info-grid">
                        <article class="admin-azure-stat admin-azure-stat-row admin-azure-stat-wide">
                            <span class="admin-azure-stat-label">Redirect URI</span>
                            <span class="admin-azure-stat-value admin-azure-stat-value-break">{{ $azureConfiguration['redirect_uri'] }}</span>
                        </article>

                        <article class="admin-azure-stat admin-azure-stat-row">
                            <span class="admin-azure-stat-label">Platform</span>
                            <span class="admin-azure-stat-value">Web</span>
                        </article>

                        <article class="admin-azure-stat admin-azure-stat-row">
                            <span class="admin-azure-stat-label">Graph access</span>
                            <span class="admin-azure-stat-value">User.Read, User.Read.All</span>
                        </article>

                        <article class="admin-azure-stat admin-azure-stat-row admin-azure-stat-wide">
                            <span class="admin-azure-stat-label">OpenID scopes</span>
                            <span class="admin-azure-stat-value">openid, profile, email, offline_access</span>
                        </article>
                    </div>
                </section>

                <section class="admin-azure-panel-section admin-azure-panel-section-form" aria-label="Azure credentials form">
                    <div>
                        <span class="setup-inline-label">Credentials</span>
                        <h4 class="admin-azure-section-title">App registration values</h4>
                    </div>

                    <div class="admin-azure-form-grid">
                        <label class="admin-label admin-azure-field">
                            Tenant ID
                            <input name="tenant_id" class="admin-input admin-input-mono" value="{{ old('tenant_id', $azureConfiguration['tenant_id'] ?? '') }}" placeholder="00000000-0000-0000-0000-000000000000" autocomplete="off" spellcheck="false">
                        </label>

                        <label class="admin-label admin-azure-field">
                            Client ID
                            <input name="client_id" class="admin-input admin-input-mono" value="{{ old('client_id', $azureConfiguration['client_id'] ?? '') }}" placeholder="00000000-0000-0000-0000-000000000000" autocomplete="off" spellcheck="false">
                        </label>

                        <label class="admin-label admin-azure-field admin-azure-field-wide">
                            Client secret
                            <input type="password" name="client_secret" class="admin-input admin-input-mono" placeholder="Paste a current Azure app registration secret" autocomplete="new-password" spellcheck="false">
                            @if ($azureConfiguration['client_secret_mask'])
                                <span class="admin-helper-text">A client secret is already stored. Enter the current secret again to verify the configuration, or paste a replacement to rotate it.</span>
                            @else
                                <span class="admin-helper-text">No client secret is stored yet.</span>
                            @endif
                        </label>

                        <label class="admin-label admin-azure-field">
                            Department source field
                            <select name="department_field" class="admin-select">
                                @foreach ($azureConfiguration['field_options'] as $fieldKey => $fieldLabel)
                                    <option value="{{ $fieldKey }}" @selected(old('department_field', $azureConfiguration['department_field']) === $fieldKey)>
                                        {{ $fieldLabel }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="admin-helper-text">Choose any retrieved Azure user profile field to populate the local department value.</span>
                        </label>

                        <label class="admin-label admin-azure-field">
                            Site source field
                            <select name="site_field" class="admin-select">
                                @foreach ($azureConfiguration['field_options'] as $fieldKey => $fieldLabel)
                                    <option value="{{ $fieldKey }}" @selected(old('site_field', $azureConfiguration['site_field']) === $fieldKey)>
                                        {{ $fieldLabel }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="admin-helper-text">Choose any retrieved Azure user profile field to populate the local site or location value.</span>
                        </label>
                    </div>
                </section>
            </div>

            <div class="admin-inline-actions">
                <button type="submit" class="admin-button">Save and verify</button>
            </div>
        </form>

        <section class="setup-permissions-panel admin-permissions-note" aria-label="Required Azure permissions">
            <div class="setup-permissions-panel-head">
                <div>
                    <span class="setup-inline-label">Required Azure permissions</span>
                    <h3 class="setup-permissions-title">What the app registration must allow</h3>
                </div>
            </div>

            <div class="setup-permission-specs setup-permission-specs-compact">
                <div class="setup-permission-spec">
                    <span class="setup-permission-label">Platform</span>
                    <div class="setup-permission-value">
                        <div class="setup-token-row">
                            <span class="setup-token">Web</span>
                        </div>
                        <p>Use the redirect URI shown above.</p>
                    </div>
                </div>

                <div class="setup-permission-spec">
                    <span class="setup-permission-label">Microsoft Graph</span>
                    <div class="setup-permission-value">
                        <div class="setup-token-row">
                            <span class="setup-token">User.Read</span>
                            <span class="setup-token">User.Read.All</span>
                        </div>
                    </div>
                </div>

                <div class="setup-permission-spec">
                    <span class="setup-permission-label">Scopes</span>
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
                    <span class="setup-permission-label">Consent</span>
                    <div class="setup-permission-value">
                        <p>Grant tenant admin consent so profile, manager, department, and site sync work reliably.</p>
                    </div>
                </div>
            </div>
        </section>
    </section>
</div>

</x-layouts.app>