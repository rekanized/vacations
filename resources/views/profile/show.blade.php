<x-layouts.app :layout-current-user="$layoutCurrentUser">
<div class="page-shell">
    @php
        $statusColors = [
            'pending' => ['bg' => 'rgba(234, 179, 8, 0.14)', 'fg' => '#a16207'],
            'approved' => ['bg' => 'rgba(34, 197, 94, 0.14)', 'fg' => '#166534'],
            'rejected' => ['bg' => 'rgba(239, 68, 68, 0.14)', 'fg' => '#b91c1c'],
        ];
    @endphp

    <section class="profile-card profile-card-featured">
        <div class="profile-card-inner">
            <div>
                <p class="profile-kicker">Request history</p>
                <h2 class="profile-section-title">Latest request decisions</h2>
            </div>

            <div class="profile-requests">
                @forelse ($requestHistory as $request)
                    @php($colors = $statusColors[$request['status']] ?? ['bg' => 'rgba(226, 232, 240, 0.9)', 'fg' => '#334155'])
                    <article class="profile-request">
                        <div class="profile-request-head">
                            <div>
                                <h3 class="profile-request-title">{{ $request['date_label'] }}</h3>
                                <div class="profile-request-copy">{{ $request['date_count'] }} day(s) · {{ $request['type'] ?? 'Unknown type' }}</div>
                            </div>
                            <span class="profile-pill" style="background: {{ $colors['bg'] }}; color: {{ $colors['fg'] }};">
                                {{ ucfirst($request['status']) }}
                            </span>
                        </div>

                        <div class="profile-request-meta">
                            <span>Submitted {{ $request['submitted_at'] ?? 'Unknown time' }}</span>
                            @if ($request['decision_at'])
                                <span>
                                    {{ ucfirst($request['status']) }} {{ $request['approver_name'] ? 'by ' . $request['approver_name'] . ' ' : '' }}on {{ $request['decision_at'] }}
                                </span>
                            @elseif ($request['status'] === 'pending')
                                <span>Awaiting manager decision</span>
                            @endif
                        </div>

                        @if ($request['reason'])
                            <div class="profile-request-copy">{{ $request['reason'] }}</div>
                        @endif
                    </article>
                @empty
                    <div class="profile-empty">No request history yet. Once you submit leave, accepted and denied decisions will appear here.</div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="profile-hero">
        <article class="profile-card">
            <div class="profile-card-inner">
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <p class="profile-kicker">Personal workspace</p>
                    <h1 class="profile-title">{{ $currentUser->name }}</h1>
                    <p class="profile-subtitle">
                        Your profile combines holiday settings, reporting lines, request outcomes, and a live planner snapshot so you can check your leave setup without jumping between pages.
                    </p>
                </div>

                <div class="profile-grid">
                    <div class="profile-detail">
                        <span class="profile-detail-label">Department</span>
                        <div class="profile-detail-value">{{ $currentUser->department?->name ?? 'No department assigned' }}</div>
                    </div>

                    <div class="profile-detail">
                        <span class="profile-detail-label">Site</span>
                        <div class="profile-detail-value">{{ $currentUser->location ?: 'No site assigned' }}</div>
                    </div>

                    <div class="profile-detail">
                        <span class="profile-detail-label">Manager</span>
                        <div class="profile-detail-value">
                            @if ($currentUser->manager)
                                {{ $currentUser->manager->name }}
                                <br>
                                {{ $currentUser->manager->department?->name ?? 'No manager department' }} · {{ $currentUser->manager->location ?: 'No manager site' }}
                            @else
                                No manager assigned
                            @endif
                        </div>
                    </div>

                    <div class="profile-detail">
                        <span class="profile-detail-label">Holiday calendar</span>
                        <div class="profile-detail-value">{{ $currentCountryName }}</div>
                    </div>
                </div>
            </div>
        </article>

        <aside class="profile-card">
            <div class="profile-card-inner">
                <div class="profile-stack">
                    <div>
                        <p class="profile-kicker">Preferences</p>
                        <h2 class="profile-section-title">Configure your workspace</h2>
                    </div>

                    <form method="POST" action="{{ route('profile.update') }}" class="profile-form">
                        @csrf
                        @method('PATCH')

                        <div class="profile-field">
                            <label for="holiday_country">Holiday country</label>
                            <select id="holiday_country" name="holiday_country" class="profile-select">
                                @foreach ($countries as $countryCode => $countryName)
                                    <option value="{{ $countryCode }}" @selected(old('holiday_country', $currentUser->holiday_country) === $countryCode)>
                                        {{ $countryName }}
                                    </option>
                                @endforeach
                            </select>
                            @error('holiday_country')
                                <div class="profile-helper" style="color: #b91c1c;">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="profile-helper">
                            Planner holiday markers update from this selection, so public holidays match the country you actually plan against.
                        </div>

                        <button type="submit" class="profile-button">Save holiday country</button>
                    </form>

                    <div class="profile-divider"></div>

                    <x-theme-toggle-form
                        :theme="$currentUser->theme_preference ?? \App\Models\User::THEME_LIGHT"
                        heading="Appearance"
                        copy="Switch the workspace between light and dark mode."
                    />
                </div>
            </div>
        </aside>
    </section>

    <section class="profile-card">
        <div class="profile-card-inner">
            <div>
                <p class="profile-kicker">Request summary</p>
                <h2 class="profile-section-title">Accepted, denied, and pending</h2>
            </div>


            <div class="profile-grid">
                <div class="profile-stat">
                    <span class="profile-stat-label">Total requests</span>
                    <span class="profile-stat-value">{{ $requestSummary['total'] }}</span>
                    <div class="profile-stat-copy">All submitted request groups tied to your planner.</div>
                </div>

                <div class="profile-stat">
                    <span class="profile-stat-label">Approved requests</span>
                    <span class="profile-stat-value">{{ $requestSummary['approved'] }}</span>
                    <div class="profile-stat-copy">Requests that were accepted and scheduled.</div>
                </div>

                <div class="profile-stat">
                    <span class="profile-stat-label">Rejected requests</span>
                    <span class="profile-stat-value">{{ $requestSummary['rejected'] }}</span>
                    <div class="profile-stat-copy">Requests denied by a manager or approver.</div>
                </div>

                <div class="profile-stat">
                    <span class="profile-stat-label">Pending requests</span>
                    <span class="profile-stat-value">{{ $requestSummary['pending'] }}</span>
                    <div class="profile-stat-copy">Requests still waiting for a decision.</div>
                </div>
            </div>
        </div>
    </section>

    <section class="profile-hero">
        <article class="profile-card">
            <div class="profile-card-inner">
                <div>
                    <p class="profile-kicker">Current planner</p>
                    <h2 class="profile-section-title">{{ $plannerSnapshot['period_label'] }}</h2>
                </div>

                <div class="profile-grid">
                    <div class="profile-stat">
                        <span class="profile-stat-label">Planned days</span>
                        <span class="profile-stat-value">{{ $plannerSnapshot['planned_days'] }}</span>
                        <div class="profile-stat-copy">Approved and pending days in the current month.</div>
                    </div>

                    <div class="profile-stat">
                        <span class="profile-stat-label">Approved days</span>
                        <span class="profile-stat-value">{{ $plannerSnapshot['approved_days'] }}</span>
                        <div class="profile-stat-copy">Days already cleared for this month.</div>
                    </div>

                    <div class="profile-stat">
                        <span class="profile-stat-label">Pending days</span>
                        <span class="profile-stat-value">{{ $plannerSnapshot['pending_days'] }}</span>
                        <div class="profile-stat-copy">Days still in the approval flow.</div>
                    </div>

                    <div class="profile-stat">
                        <span class="profile-stat-label">Rejected days</span>
                        <span class="profile-stat-value">{{ $plannerSnapshot['rejected_days'] }}</span>
                        <div class="profile-stat-copy">Rejected planner days in the current month.</div>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <h3 class="profile-section-title" style="font-size: 17px;">Next planned periods</h3>

                    @forelse ($plannerSnapshot['upcoming_requests'] as $request)
                        <div class="profile-upcoming-item">
                            <div class="profile-request-head">
                                <strong>{{ $request['date_label'] }}</strong>
                                @php($colors = $statusColors[$request['status']] ?? ['bg' => 'rgba(226, 232, 240, 0.9)', 'fg' => '#334155'])
                                <span class="profile-pill" style="background: {{ $colors['bg'] }}; color: {{ $colors['fg'] }};">
                                    {{ ucfirst($request['status']) }}
                                </span>
                            </div>
                            <div class="profile-helper">
                                {{ $request['date_count'] }} day(s) · {{ $request['type'] ?? 'Unknown type' }}
                                @if ($request['reason'])
                                    · {{ $request['reason'] }}
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="profile-empty">No upcoming planned periods yet. Use the <a href="{{ route('planner') }}" class="profile-link">planner</a> to add one.</div>
                    @endforelse
                </div>
            </div>
        </article>

        <aside class="profile-card">
            <div class="profile-card-inner">
                <div>
                    <p class="profile-kicker">Reporting line</p>
                    <h2 class="profile-section-title">Who you are connected to</h2>
                </div>

                <div class="profile-grid" style="grid-template-columns: 1fr;">
                    <div class="profile-detail">
                        <span class="profile-detail-label">You</span>
                        <div class="profile-detail-value">{{ $currentUser->name }} · {{ $currentUser->department?->name ?? 'No department' }} · {{ $currentUser->location ?: 'No site' }}</div>
                    </div>

                    <div class="profile-detail">
                        <span class="profile-detail-label">Manager</span>
                        <div class="profile-detail-value">
                            @if ($currentUser->manager)
                                {{ $currentUser->manager->name }} · {{ $currentUser->manager->department?->name ?? 'No department' }} · {{ $currentUser->manager->location ?: 'No site' }}
                            @else
                                No manager assigned. Your requests are auto-approved.
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </section>

</div>
</x-layouts.app>