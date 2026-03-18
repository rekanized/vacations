<x-layouts.app :layout-current-user="$layoutCurrentUser ?? null">
<div class="page-shell page-shell-fluid">
    <section class="admin-card admin-stack">
        <div class="admin-toolbar">
            <div>
                <h1 style="font-size: 28px;">Request log</h1>
                <p style="color: #475569; max-width: 760px; margin-bottom: 0;">
                    Audit trail for absence activity and admin user-status changes.
                </p>
            </div>

            <a href="{{ route('admin.index') }}" class="admin-button secondary">Back to admin</a>
        </div>

        <form method="GET" action="{{ route('admin.logs') }}" class="admin-form">
            <label class="admin-label">
                Search
                <input
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    class="admin-input"
                    placeholder="User, actor, reason, or request ID"
                >
            </label>

            <label class="admin-label">
                Action
                <select name="action" class="admin-select">
                    <option value="">All actions</option>
                    @foreach ($actionOptions as $value => $label)
                        <option value="{{ $value }}" @selected($action === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <x-loading-button type="submit" class="admin-button">Apply filters</x-loading-button>
                <a href="{{ route('admin.logs') }}" class="admin-button ghost">Reset</a>
            </div>
        </form>
    </section>

    <section class="admin-card admin-stack">
        @if ($logs->isEmpty())
            <div class="empty-state">
                No log entries matched the current filters.
            </div>
        @else
            <div class="log-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Action</th>
                            <th>Requester</th>
                            <th>Actor</th>
                            <th>Dates</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            @php
                                $metadata = $log->metadata ?? [];
                            @endphp
                            <tr>
                                <td>
                                    <div class="log-detail">
                                        <strong>{{ $log->created_at?->format('Y-m-d H:i') }}</strong>
                                        <span class="log-muted">
                                            {{ $log->request_uuid ? \Illuminate\Support\Str::limit($log->request_uuid, 12, '…') : 'No request ID' }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="log-chip" data-action="{{ $log->action }}">
                                        {{ $actionOptions[$log->action] ?? ucfirst($log->action) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="log-detail">
                                        <strong>{{ $log->user?->name ?? 'Unknown user' }}</strong>
                                        <span class="log-muted">{{ $log->user?->department?->name ?? 'No department' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="log-detail">
                                        <strong>{{ $log->actor?->name ?? 'System' }}</strong>
                                        <span class="log-muted">{{ $log->actor?->department?->name ?? '—' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="log-detail">
                                        <strong>
                                            {{ $log->date_start?->format('Y-m-d') ?? '—' }}
                                            @if ($log->date_end && $log->date_start?->format('Y-m-d') !== $log->date_end?->format('Y-m-d'))
                                                → {{ $log->date_end?->format('Y-m-d') }}
                                            @endif
                                        </strong>
                                        <span class="log-muted">{{ $log->date_count }} day{{ $log->date_count === 1 ? '' : 's' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="log-detail">
                                        <div class="log-meta-list">
                                            @if ($log->absence_type)
                                                <span class="log-meta-pill">Type: {{ $log->absence_type }}</span>
                                            @endif
                                            @if ($log->status)
                                                <span class="log-meta-pill">Status: {{ ucfirst($log->status) }}</span>
                                            @endif
                                            @if (($metadata['approval_flow'] ?? null) === 'automatic')
                                                <span class="log-meta-pill">Auto-approved flow</span>
                                            @endif
                                            @if (($metadata['source'] ?? null) === 'planner_grid')
                                                <span class="log-meta-pill">Removed from planner</span>
                                            @endif
                                            @if (($metadata['source'] ?? null) === 'admin_user_management')
                                                <span class="log-meta-pill">Admin user management</span>
                                            @endif
                                        </div>

                                        @if ($log->reason)
                                            <span class="log-muted">Reason: {{ $log->reason }}</span>
                                        @endif

                                        @if ($metadata['decision_reason'] ?? null)
                                            <span class="log-muted">Manager reason: {{ $metadata['decision_reason'] }}</span>
                                        @endif

                                        @if (($metadata['before']['date_start'] ?? null) && ($metadata['after']['date_start'] ?? null))
                                            <span class="log-muted">
                                                Changed from {{ $metadata['before']['date_start'] }}
                                                @if (($metadata['before']['date_end'] ?? null) && $metadata['before']['date_end'] !== $metadata['before']['date_start'])
                                                    → {{ $metadata['before']['date_end'] }}
                                                @endif
                                                to {{ $metadata['after']['date_start'] }}
                                                @if (($metadata['after']['date_end'] ?? null) && $metadata['after']['date_end'] !== $metadata['after']['date_start'])
                                                    → {{ $metadata['after']['date_end'] }}
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pagination-row">
                <span class="log-muted">
                    Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }} entries
                </span>

                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    @if ($logs->onFirstPage())
                        <span class="pagination-link secondary" style="opacity: 0.6; cursor: default;">Previous</span>
                    @else
                        <a href="{{ $logs->previousPageUrl() }}" class="pagination-link secondary">Previous</a>
                    @endif

                    @if ($logs->hasMorePages())
                        <a href="{{ $logs->nextPageUrl() }}" class="pagination-link">Next</a>
                    @else
                        <span class="pagination-link secondary" style="opacity: 0.6; cursor: default;">Next</span>
                    @endif
                </div>
            </div>
        @endif
    </section>
</div>
</x-layouts.app>
