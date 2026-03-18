<x-layouts.app>
@php
    $shouldOpenAddOptionModal = $errors->any()
        && old('option_id') === null
        && (old('code') !== null || old('label') !== null || old('color') !== null);
    $activeUserCount = $users->where('is_active', true)->count();
    $impersonationUsers = $users
        ->filter(fn ($user) => $user->is_active)
        ->map(function ($user) {
            $departmentName = $user->department?->name ?? 'No department';
            $locationName = $user->location ?: 'No location';
            $managerName = $user->manager?->name ?? 'No manager';

            return [
                'id' => (string) $user->id,
                'name' => $user->name,
                'department' => $departmentName,
                'location' => $locationName,
                'manager' => $managerName,
                'label' => sprintf('%s · %s · %s', $user->name, $departmentName, $locationName),
                'search' => implode(' ', [
                    $user->name,
                    $departmentName,
                    $locationName,
                ]),
            ];
        })
        ->values();
    $initialImpersonationUserId = (string) old('user_id', '');
@endphp

<div
    x-data="{
        showAddOptionModal: @js($shouldOpenAddOptionModal),
        impersonationUsers: @js($impersonationUsers),
        impersonationOpen: false,
        impersonationQuery: '',
        selectedImpersonationUserId: @js($initialImpersonationUserId),
        init() {
            this.syncImpersonationQuery();
        },
        get selectedImpersonationUser() {
            return this.impersonationUsers.find((user) => String(user.id) === String(this.selectedImpersonationUserId)) ?? null;
        },
        get filteredImpersonationUsers() {
            const query = this.impersonationQuery.trim();

            if (!query) {
                return this.impersonationUsers;
            }

            const normalized = query.toLowerCase();
            const wildcardQuery = normalized.includes('*') ? normalized : `*${normalized}*`;
            const escaped = wildcardQuery.replace(/[.+?^${}()|[\]\\]/g, '\\$&').replace(/\*/g, '.*');
            const matcher = new RegExp(escaped, 'i');

            return this.impersonationUsers.filter((user) => matcher.test(user.search.toLowerCase()));
        },
        get visibleImpersonationUsers() {
            return this.filteredImpersonationUsers.slice(0, 50);
        },
        syncImpersonationQuery() {
            this.impersonationQuery = this.selectedImpersonationUser?.label ?? '';
        },
        openImpersonationDropdown() {
            this.impersonationOpen = true;
        },
        closeImpersonationDropdown() {
            this.impersonationOpen = false;
            this.syncImpersonationQuery();
        },
        clearImpersonationQuery() {
            this.impersonationQuery = '';
            this.impersonationOpen = true;
        },
        selectImpersonationUser(user) {
            this.selectedImpersonationUserId = String(user.id);
            this.impersonationQuery = user.label;
            this.impersonationOpen = false;
        },
        selectFirstVisibleImpersonationUser() {
            if (this.visibleImpersonationUsers.length === 0) {
                return;
            }

            this.selectImpersonationUser(this.visibleImpersonationUsers[0]);
        }
    }"
    @keydown.escape.window="showAddOptionModal = false"
    style="max-width: 1200px; margin: 0 auto; padding: 32px 24px 48px; display: flex; flex-direction: column; gap: 24px;"
>
    <style>
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
        }

        .admin-card {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 20px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            padding: 24px;
        }

        .admin-card h1,
        .admin-card h2,
        .admin-card h3,
        .admin-card p {
            margin-top: 0;
        }

        .admin-form {
            display: grid;
            gap: 14px;
        }

        .admin-label {
            display: grid;
            gap: 8px;
            font-size: 14px;
            color: #334155;
            font-weight: 600;
        }

        .admin-input,
        .admin-select {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 12px 14px;
            font: inherit;
            background: white;
            color: #0f172a;
        }

        .admin-combobox {
            position: relative;
        }

        .admin-combobox-input-wrap {
            position: relative;
        }

        .admin-combobox-input {
            padding-right: 48px;
        }

        .admin-combobox-clear {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #64748b;
            font-size: 18px;
            line-height: 1;
            cursor: pointer;
            padding: 4px;
        }

        .admin-combobox-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            z-index: 30;
            background: white;
            border: 1px solid rgba(148, 163, 184, 0.3);
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.16);
            overflow: hidden;
        }

        .admin-combobox-list {
            max-height: 320px;
            overflow: auto;
            display: grid;
        }

        .admin-combobox-option,
        .admin-combobox-empty {
            width: 100%;
            padding: 12px 14px;
            text-align: left;
            background: white;
            border: none;
        }

        .admin-combobox-option {
            cursor: pointer;
            display: grid;
            gap: 4px;
        }

        .admin-combobox-option:hover,
        .admin-combobox-option:focus-visible,
        .admin-combobox-option.active {
            background: #eff6ff;
            outline: none;
        }

        .admin-combobox-title {
            font-weight: 700;
            color: #0f172a;
        }

        .admin-combobox-meta {
            font-size: 13px;
            color: #475569;
        }

        .admin-combobox-empty,
        .admin-combobox-footnote {
            font-size: 13px;
            color: #475569;
        }

        .admin-combobox-footnote {
            padding: 10px 14px 12px;
            border-top: 1px solid rgba(148, 163, 184, 0.18);
            background: #f8fafc;
        }

        .admin-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 12px;
            background: #0f172a;
            color: white;
            padding: 12px 18px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .admin-button.secondary {
            background: #e2e8f0;
            color: #334155;
        }

        .admin-button.danger {
            background: #b91c1c;
            color: white;
        }

        .admin-button-link {
            text-decoration: none;
        }

        .admin-button-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .admin-inline-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .admin-inline-actions > * {
            flex: 1 1 140px;
        }

        .admin-table-input {
            min-width: 0;
            padding: 10px 12px;
        }

        .admin-color-field {
            display: grid;
            gap: 8px;
        }

        .admin-options-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 18px;
        }

        .admin-option-card {
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 20px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96));
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.08);
            padding: 18px;
            display: grid;
            gap: 16px;
        }

        .admin-option-card-header {
            display: grid;
            gap: 10px;
        }

        .admin-option-fields {
            display: grid;
            gap: 14px;
        }

        .admin-option-field-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 120px;
            gap: 12px;
            align-items: end;
        }

        .admin-color-preview {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 48px;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: white;
        }

        .admin-option-meta {
            display: grid;
            gap: 6px;
        }

        .admin-option-footer {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: auto;
        }

        .admin-option-usage {
            min-height: 74px;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 16px;
        }

        .modal-content {
            background: white;
            padding: 32px;
            border-radius: 20px;
            width: min(460px, 100%);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .modal-title {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }

        .modal-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            margin-top: 12px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-primary { background: #0f172a; color: white; }
        .btn-secondary { background: #f1f5f9; color: #64748b; }

        .btn:hover { opacity: 0.9; transform: translateY(-1px); }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th,
        .admin-table td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }

        .admin-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 13px;
            font-weight: 700;
        }

        .admin-chip.muted {
            background: #f1f5f9;
            color: #475569;
        }

        .admin-chip.success {
            background: #dcfce7;
            color: #166534;
        }

        .admin-chip.warning {
            background: #fee2e2;
            color: #991b1b;
        }

        .admin-user-row.inactive td {
            color: #64748b;
            background: rgba(248, 250, 252, 0.85);
        }

        .admin-helper-text {
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
            margin: 0;
        }

        .option-dot {
            width: 12px;
            height: 12px;
            border-radius: 999px;
            display: inline-block;
        }

        .error-list {
            margin: 0;
            padding-left: 20px;
            color: #b91c1c;
        }

        .usage-warning {
            display: grid;
            gap: 4px;
            padding: 12px;
            border-radius: 14px;
            background: rgba(254, 242, 242, 0.9);
            border: 1px solid rgba(248, 113, 113, 0.24);
            color: #991b1b;
            font-size: 13px;
            line-height: 1.45;
        }

        .usage-note {
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
            margin: 0;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        @media (max-width: 720px) {
            .admin-option-field-row {
                grid-template-columns: 1fr;
            }

            .admin-inline-actions > * {
                flex-basis: 100%;
            }
        }
    </style>

    <section class="admin-card">
        <h1 style="font-size: 28px;">Admin proof of concept</h1>
        <p style="color: #475569; max-width: 760px;">
            Anyone can access this page. Use it to impersonate a user and add, edit, or delete absence options while the approval flow is being validated.
        </p>

        @if ($errors->any())
            <ul class="error-list">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        @if ($currentUser)
            <div class="admin-chip">
                Acting as {{ $currentUser->name }}
                @if ($currentUser->manager)
                    <span style="color: #475569; font-weight: 600;">· Manager: {{ $currentUser->manager->name }}</span>
                @endif
            </div>
        @endif
    </section>

    <div class="admin-grid">
        <section class="admin-card">
            <h2>Application name</h2>
            <p style="color: #475569;">Change the product name shown in the sidebar and page title.</p>

            <form method="POST" action="{{ route('admin.application-name.update') }}" class="admin-form">
                @csrf
                <label class="admin-label">
                    App name
                    <input name="app_name" class="admin-input" maxlength="80" value="{{ old('app_name', $applicationName) }}" placeholder="LeaveBoard">
                </label>

                <button type="submit" class="admin-button">Save name</button>
            </form>
        </section>

        <section class="admin-card">
            <h2>Impersonate a user</h2>
            <p style="color: #475569;">Switch the active user stored in the session. Only active users appear here. Use <strong>*</strong> as a wildcard when searching.</p>

            <form method="POST" action="{{ route('admin.impersonate') }}" class="admin-form">
                @csrf
                <label class="admin-label">
                    User
                    <div class="admin-combobox" @click.outside="closeImpersonationDropdown()">
                        <input type="hidden" name="user_id" :value="selectedImpersonationUserId">

                        <div class="admin-combobox-input-wrap">
                            <input
                                type="text"
                                class="admin-input admin-combobox-input"
                                x-model="impersonationQuery"
                                @focus="openImpersonationDropdown()"
                                @click="openImpersonationDropdown()"
                                @input="openImpersonationDropdown()"
                                @keydown.enter.prevent="selectFirstVisibleImpersonationUser()"
                                @keydown.escape.prevent.stop="closeImpersonationDropdown()"
                                placeholder="Search user, department, or location"
                                autocomplete="off"
                                spellcheck="false"
                            >

                            <button
                                type="button"
                                class="admin-combobox-clear"
                                @click="clearImpersonationQuery()"
                                x-show="impersonationQuery !== ''"
                                x-cloak
                                aria-label="Clear user search"
                            >
                                ×
                            </button>
                        </div>

                        <div class="admin-combobox-dropdown" x-show="impersonationOpen" x-cloak>
                            <div class="admin-combobox-list">
                                <template x-if="visibleImpersonationUsers.length === 0">
                                    <div class="admin-combobox-empty">
                                        No users matched that search.
                                    </div>
                                </template>

                                <template x-for="user in visibleImpersonationUsers" :key="user.id">
                                    <button
                                        type="button"
                                        class="admin-combobox-option"
                                        :class="{ 'active': String(user.id) === String(selectedImpersonationUserId) }"
                                        @click="selectImpersonationUser(user)"
                                    >
                                        <span class="admin-combobox-title" x-text="user.name"></span>
                                        <span class="admin-combobox-meta" x-text="`${user.department} · ${user.location} · Manager: ${user.manager}`"></span>
                                    </button>
                                </template>
                            </div>

                            <div class="admin-combobox-footnote">
                                <span x-text="filteredImpersonationUsers.length > 50 ? `Showing the first 50 of ${filteredImpersonationUsers.length} matches.` : `${filteredImpersonationUsers.length} match(es).`"></span>
                                Type any part of the person details, or use <strong>*</strong> as a wildcard.
                            </div>
                        </div>
                    </div>
                </label>

                <button type="submit" class="admin-button">Impersonate</button>
            </form>
        </section>

        <section class="admin-card">
            <h2>Request log</h2>
            <p style="color: #475569;">
                Review submitted, updated, approved, rejected, and deleted absence requests.
            </p>

            <div class="admin-chip" style="margin-bottom: 16px;">
                {{ $requestLogCount }} logged events
            </div>

            <a href="{{ route('admin.logs') }}" class="admin-button admin-button-link">Open log view</a>
        </section>
    </div>

    <section class="admin-card">
        <div class="admin-button-row" style="margin-bottom: 16px;">
            <div>
                <h2>Current absence options</h2>
                <p style="color: #475569; max-width: 760px; margin-bottom: 0;">
                    If an option has already been used, the warning below appears before you save or delete it.
                </p>
            </div>

            <button type="button" class="admin-button" @click="showAddOptionModal = true">
                Add absence option
            </button>
        </div>

        <div class="admin-options-grid">
            @foreach ($absenceOptions as $option)
                @php
                    $isEditingOption = (string) old('option_id') === (string) $option->id;
                    $labelValue = $isEditingOption ? old('label', $option->label) : $option->label;
                    $codeValue = $isEditingOption ? old('code', $option->code) : $option->code;
                    $colorValue = $isEditingOption ? old('color', $option->color) : $option->color;
                    $hasUsage = $option->user_count > 0;
                    $editConfirmation = $hasUsage
                        ? sprintf('Warning: %d people have already used %s for %d day(s). Saving will update this option, and changing the code will also update those existing days. Continue?', $option->user_count, $option->label, $option->absence_count)
                        : sprintf('Save changes to %s?', $option->label);
                    $deleteConfirmation = $hasUsage
                        ? sprintf('Warning: %d people have already used %s for %d day(s). Deleting will remove it from future selections, but existing days will still keep the deleted code. Continue?', $option->user_count, $option->label, $option->absence_count)
                        : sprintf('Delete %s?', $option->label);
                @endphp

                <article class="admin-option-card">
                    <form
                        id="update-option-{{ $option->id }}"
                        method="POST"
                        action="{{ route('admin.absence-options.update', $option) }}"
                        class="admin-option-fields"
                        onsubmit="return confirm(@js($editConfirmation));"
                    >
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="option_id" value="{{ $option->id }}">

                        <div class="admin-option-card-header">
                            <span class="admin-chip" style="background: {{ $colorValue }}20; color: #0f172a; width: fit-content;">
                                <span class="option-dot" style="background: {{ $colorValue }};"></span>
                                {{ $labelValue }}
                            </span>

                            <div class="admin-option-meta">
                                <p class="usage-note">Edit the option details below. Changes appear in the planner immediately.</p>
                            </div>
                        </div>

                        <label class="admin-label" for="option-label-{{ $option->id }}">
                            Label
                            <input
                                id="option-label-{{ $option->id }}"
                                name="label"
                                class="admin-input admin-table-input"
                                value="{{ $labelValue }}"
                                maxlength="100"
                            >
                        </label>

                        <div class="admin-option-field-row">
                            <label class="admin-label" for="option-code-{{ $option->id }}">
                                Code
                                <input
                                    id="option-code-{{ $option->id }}"
                                    name="code"
                                    class="admin-input admin-table-input"
                                    value="{{ $codeValue }}"
                                    maxlength="10"
                                >
                            </label>

                            <label class="admin-label" for="option-color-picker-{{ $option->id }}">
                                Color
                                <input
                                    id="option-color-picker-{{ $option->id }}"
                                    type="color"
                                    name="color"
                                    class="admin-input admin-table-input"
                                    value="{{ $colorValue }}"
                                    style="padding: 6px 8px; min-height: 48px;"
                                >
                            </label>
                        </div>

                        <div class="admin-color-preview">
                            <span class="option-dot" style="width: 16px; height: 16px; background: {{ $colorValue }};"></span>
                            <span style="font-weight: 600; color: #0f172a;">{{ $colorValue }}</span>
                        </div>
                    </form>

                    <div class="admin-option-footer">
                        <div class="admin-option-usage">
                            @if ($hasUsage)
                                <div class="usage-warning">
                                    <span><strong>Warning:</strong> {{ $option->user_count }} people already used this option.</span>
                                    <span>{{ $option->absence_count }} day(s) will be affected by changes.</span>
                                </div>
                            @else
                                <p class="usage-note">No existing usage yet.</p>
                            @endif
                        </div>

                        <div class="admin-inline-actions">
                            <button type="submit" form="update-option-{{ $option->id }}" class="admin-button">Save</button>

                            <form
                                method="POST"
                                action="{{ route('admin.absence-options.destroy', $option) }}"
                                onsubmit="return confirm(@js($deleteConfirmation));"
                            >
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-button danger" style="width: 100%;">Delete</button>
                            </form>
                        </div>

                        @if ($hasUsage)
                            <p class="usage-note">
                                Deleting removes the option from future choices, while existing days keep their stored code.
                            </p>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <div class="modal-overlay" x-show="showAddOptionModal" x-cloak x-transition>
        <div class="modal-content" @click.away="showAddOptionModal = false">
            <div>
                <h2 class="modal-title">Add absence option</h2>
                <p style="color: #475569; margin: 8px 0 0;">New options become available in the planner modal immediately.</p>
            </div>

            <form method="POST" action="{{ route('admin.absence-options.store') }}" class="admin-form">
                @csrf
                <label class="admin-label">
                    Code
                    <input name="code" class="admin-input" maxlength="10" value="{{ old('option_id') ? '' : old('code') }}" placeholder="WFH">
                </label>

                <label class="admin-label">
                    Label
                    <input name="label" class="admin-input" value="{{ old('option_id') ? '' : old('label') }}" placeholder="Work from home">
                </label>

                <label class="admin-label">
                    Color
                    <input type="color" name="color" class="admin-input" value="{{ old('option_id') ? '#4ade80' : old('color', '#4ade80') }}" style="padding: 6px 8px; min-height: 48px;">
                </label>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" @click="showAddOptionModal = false">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add option</button>
                </div>
            </form>
        </div>
    </div>

    <section class="admin-card">
        <h2>Users and managers</h2>
        <p style="color: #475569; max-width: 760px;">Inactive users are hidden from impersonation, session fallback, planner filters, and planner rosters until they are reactivated.</p>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Department</th>
                    <th>Manager</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    @php
                        $canDeactivate = $user->is_active && $activeUserCount > 1;
                    @endphp
                    <tr class="admin-user-row {{ $user->is_active ? '' : 'inactive' }}">
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->department?->name ?? '—' }}</td>
                        <td>{{ $user->manager?->name ?? 'No manager' }}</td>
                        <td>
                            <span class="admin-chip {{ $user->is_active ? 'success' : 'warning' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
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
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
</div>

</x-layouts.app>
