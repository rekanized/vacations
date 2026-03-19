<x-layouts.app :layout-current-user="$layoutCurrentUser">
@php
    $activeUserCount = $users->where('is_active', true)->count();
@endphp

<div class="page-shell page-shell-admin">
    <section class="admin-card admin-stack">
        <div>
            <p class="planner-kicker">Admin workspace</p>
            <h1 style="font-size: 28px; margin-bottom: 12px;">User information</h1>
            <p style="color: var(--text-soft); max-width: 760px; margin: 0;">
                Manage manual accounts, review who can access the application, and control admin permissions without mixing those tasks into application configuration.
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

    <section class="admin-card admin-stack">
        <div>
            <h2>Manual users</h2>
            <p style="color: var(--text-soft); max-width: 760px; margin-bottom: 0;">
                Add manual email/password users when Azure is not available for everyone. Azure remains the recommended setup.
            </p>
        </div>

        <form method="POST" action="{{ route('admin.manual-users.store') }}" class="admin-form admin-form-inline">
            @csrf

            <label class="admin-label">
                Name
                <input name="first_name" class="admin-input" value="{{ old('first_name') }}" placeholder="Asta">
            </label>

            <label class="admin-label">
                Lastname
                <input name="last_name" class="admin-input" value="{{ old('last_name') }}" placeholder="Admin">
            </label>

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
                <input type="password" name="password_confirmation" class="admin-input" placeholder="Repeat password">
            </label>

            <label class="admin-label">
                Department
                <input name="department_name" class="admin-input" value="{{ old('department_name') }}" list="department-name-options" placeholder="Unassigned">
            </label>

            <label class="admin-label">
                Location
                <input name="location" class="admin-input" value="{{ old('location') }}" placeholder="Unassigned">
            </label>

            <label class="admin-label">
                Manager
                <select name="manager_id" class="admin-select">
                    <option value="">No manager</option>
                    @foreach ($users as $managerOption)
                        @if ($managerOption->is_active)
                            <option value="{{ $managerOption->id }}" @selected((string) old('manager_id', '') === (string) $managerOption->id)>
                                {{ $managerOption->name }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </label>

            <label class="admin-checkbox-label">
                <input type="checkbox" name="is_admin" value="1" @checked(old('is_admin'))>
                Grant admin access
            </label>

            <div class="admin-inline-actions">
                <button type="submit" class="admin-button secondary">Add manual user</button>
            </div>
        </form>

        <datalist id="department-name-options">
            @foreach ($departmentOptions as $departmentOption)
                <option value="{{ $departmentOption }}"></option>
            @endforeach
        </datalist>
    </section>

    <section class="admin-card">
        <h2>Users and permissions</h2>
        <p style="color: var(--text-soft); max-width: 760px;">New Azure users are created automatically on first sign-in. Admin access can be delegated here, and inactive users are excluded from sign-in and planner participation.</p>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Department</th>
                    <th>Manager</th>
                    <th>Access</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    @php
                        $canDeactivate = $user->is_active && $activeUserCount > 1;
                        $canRevokeAdmin = $user->is_admin && $adminCount > 1;
                    @endphp
                    <tr class="admin-user-row {{ $user->is_active ? '' : 'inactive' }}">
                        <td>
                            <div class="admin-user-copy">
                                <strong>{{ $user->name }}</strong>
                                <span class="admin-helper-text">{{ $user->email ?: 'No email captured yet' }}</span>
                            </div>
                        </td>
                        <td>{{ $user->department?->name ?? '—' }}</td>
                        <td>
                            <div class="admin-user-copy">
                                <span>{{ $user->manager?->name ?? 'No manager' }}</span>

                                @if ($user->isManualAccount())
                                    <form method="POST" action="{{ route('admin.users.manager', $user) }}" class="admin-form admin-user-manager-form">
                                        @csrf
                                        @method('PATCH')

                                        <label class="admin-label" for="manager-id-{{ $user->id }}">
                                            <span class="admin-helper-text">Assign manager</span>
                                            <select id="manager-id-{{ $user->id }}" name="manager_id" class="admin-select">
                                                <option value="">No manager</option>
                                                @foreach ($users as $managerOption)
                                                    @if ($managerOption->id !== $user->id && $managerOption->is_active)
                                                        <option value="{{ $managerOption->id }}" @selected($user->manager_id === $managerOption->id)>
                                                            {{ $managerOption->name }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </label>

                                        <button type="submit" class="admin-button secondary">Save manager</button>
                                    </form>
                                @else
                                    <span class="admin-helper-text">Managed from Azure profile data</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="admin-chip {{ $user->is_admin ? 'success' : '' }}">
                                {{ $user->is_admin ? 'Admin' : 'Standard' }}
                            </span>
                        </td>
                        <td>
                            <span class="admin-chip {{ $user->is_active ? 'success' : 'warning' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <div class="admin-user-actions">
                                <form method="POST" action="{{ route('admin.users.admin', $user) }}" class="admin-form" style="gap: 8px;">
                                    @csrf
                                    @method('PATCH')

                                    <button
                                        type="submit"
                                        class="admin-button {{ $user->is_admin ? 'secondary' : '' }}"
                                        @disabled($user->is_admin && ! $canRevokeAdmin)
                                    >
                                        {{ $user->is_admin ? 'Remove admin' : 'Grant admin' }}
                                    </button>

                                    @if ($user->is_admin && ! $canRevokeAdmin)
                                        <p class="admin-helper-text">At least one admin must remain.</p>
                                    @endif
                                </form>

                                <form method="POST" action="{{ route('admin.users.activity', $user) }}" class="admin-form" style="gap: 8px;">
                                    @csrf
                                    @method('PATCH')

                                    <button
                                        type="submit"
                                        class="admin-button {{ $user->is_active ? 'secondary' : '' }}"
                                        @disabled($user->is_active && ! $canDeactivate)
                                    >
                                        {{ $user->is_active ? 'Mark inactive' : 'Reactivate' }}
                                    </button>

                                    @if ($user->is_active && ! $canDeactivate)
                                        <p class="admin-helper-text">At least one active user must remain.</p>
                                    @endif
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
</div>

</x-layouts.app>