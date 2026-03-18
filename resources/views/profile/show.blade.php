<x-layouts.app :layout-current-user="$layoutCurrentUser">
<div style="max-width: 1240px; margin: 0 auto; padding: 32px 24px 48px; display: flex; flex-direction: column; gap: 24px;">
    @php
        $statusColors = [
            'pending' => ['bg' => 'rgba(234, 179, 8, 0.14)', 'fg' => '#a16207'],
            'approved' => ['bg' => 'rgba(34, 197, 94, 0.14)', 'fg' => '#166534'],
            'rejected' => ['bg' => 'rgba(239, 68, 68, 0.14)', 'fg' => '#b91c1c'],
        ];
    @endphp

    <style>
        .profile-hero {
            display: grid;
            grid-template-columns: minmax(0, 1.6fr) minmax(320px, 1fr);
            gap: 24px;
        }

        .profile-card {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 24px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        }

        .profile-card-inner {
            padding: 28px;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .profile-kicker {
            margin: 0;
            color: #2563eb;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .profile-title {
            margin: 0;
            font-size: 32px;
            line-height: 1.05;
            letter-spacing: -0.04em;
            color: #0f172a;
        }

        .profile-subtitle {
            margin: 0;
            color: #475569;
            font-size: 14px;
            line-height: 1.6;
            max-width: 60ch;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
        }

        .profile-stat,
        .profile-detail,
        .profile-request,
        .profile-upcoming-item {
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, 0.18);
            background: rgba(248, 250, 252, 0.9);
        }

        .profile-stat,
        .profile-detail,
        .profile-upcoming-item {
            padding: 18px;
        }

        .profile-stat-label,
        .profile-detail-label {
            display: block;
            margin-bottom: 8px;
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .profile-stat-value {
            display: block;
            color: #0f172a;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.04em;
        }

        .profile-stat-copy,
        .profile-detail-value,
        .profile-helper {
            color: #475569;
            font-size: 13px;
            line-height: 1.6;
        }

        .profile-section-title {
            margin: 0;
            font-size: 20px;
            color: #0f172a;
            letter-spacing: -0.03em;
        }

        .profile-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .profile-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .profile-field label {
            color: #334155;
            font-size: 13px;
            font-weight: 700;
        }

        .profile-select {
            width: 100%;
            padding: 12px 14px;
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.3);
            background: white;
            color: #0f172a;
            font: inherit;
        }

        .profile-button {
            align-self: flex-start;
            padding: 12px 18px;
            border: 0;
            border-radius: 999px;
            background: linear-gradient(135deg, #2563eb, #0ea5e9);
            color: white;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 18px 34px rgba(37, 99, 235, 0.24);
        }

        .profile-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .profile-requests {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .profile-request {
            padding: 18px 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .profile-request-head,
        .profile-request-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .profile-request-title {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
        }

        .profile-request-copy,
        .profile-request-meta {
            color: #475569;
            font-size: 13px;
            line-height: 1.6;
        }

        .profile-empty {
            padding: 18px;
            border-radius: 18px;
            border: 1px dashed rgba(148, 163, 184, 0.28);
            background: rgba(248, 250, 252, 0.9);
            color: #64748b;
            font-size: 14px;
        }

        .profile-link {
            color: #2563eb;
            text-decoration: none;
            font-weight: 700;
        }

        @media (max-width: 960px) {
            .profile-hero {
                grid-template-columns: 1fr;
            }
        }
    </style>

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
                <div>
                    <p class="profile-kicker">Holiday country</p>
                    <h2 class="profile-section-title">Configure your calendar</h2>
                </div>

                <form method="POST" action="{{ route('profile.update') }}" class="profile-form">
                    @csrf
                    @method('PATCH')

                    <div class="profile-field">
                        <label for="holiday_country">Country</label>
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

    <section class="profile-card">
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
</div>
</x-layouts.app>