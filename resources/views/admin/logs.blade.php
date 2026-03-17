<x-layouts.app>
<div style="max-width: 1280px; margin: 0 auto; padding: 32px 24px 48px; display: flex; flex-direction: column; gap: 24px;">
    @php
        $actionColors = [
            'submitted' => ['bg' => '#dbeafe', 'fg' => '#1d4ed8'],
            'updated' => ['bg' => '#ede9fe', 'fg' => '#6d28d9'],
            'deleted' => ['bg' => '#fee2e2', 'fg' => '#b91c1c'],
            'approved' => ['bg' => '#dcfce7', 'fg' => '#166534'],
            'rejected' => ['bg' => '#fef3c7', 'fg' => '#92400e'],
        ];
    @endphp

    <style>
        .admin-card {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 20px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            padding: 24px;
        }

        .admin-card h1,
        .admin-card h2,
        .admin-card p {
            margin-top: 0;
        }

        .admin-stack {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .admin-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            flex-wrap: wrap;
        }

        .admin-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
            align-items: end;
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

        .admin-button,
        .pagination-link {
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
            text-decoration: none;
        }

        .admin-button.secondary,
        .pagination-link.secondary {
            background: #e2e8f0;
            color: #334155;
        }

        .admin-button.ghost {
            background: transparent;
            border: 1px solid #cbd5e1;
            color: #334155;
        }

        .log-table-wrap {
            overflow-x: auto;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th,
        .admin-table td {
            text-align: left;
            vertical-align: top;
            padding: 14px 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }

        .admin-table th {
            color: #475569;
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .log-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 11px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .log-detail {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 220px;
        }

        .log-muted {
            color: #64748b;
            font-size: 13px;
            line-height: 1.5;
        }

        .log-meta-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .log-meta-pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #334155;
            font-size: 12px;
            font-weight: 700;
        }

        .pagination-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .empty-state {
            padding: 18px;
            border-radius: 14px;
            background: rgba(248, 250, 252, 0.95);
            color: #64748b;
            font-size: 14px;
            border: 1px dashed rgba(148, 163, 184, 0.3);
        }
    </style>

    <section class="admin-card admin-stack">
        <div class="admin-toolbar">
            <div>
                <h1 style="font-size: 28px;">Request log</h1>
                <p style="color: #475569; max-width: 760px; margin-bottom: 0;">
                    Audit trail for absence submissions, edits, approvals, rejections, and deletions.
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
                <button type="submit" class="admin-button">Apply filters</button>
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
                                $colors = $actionColors[$log->action] ?? ['bg' => '#e2e8f0', 'fg' => '#334155'];
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
                                    <span class="log-chip" style="background: {{ $colors['bg'] }}; color: {{ $colors['fg'] }};">
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
                                        </div>

                                        @if ($log->reason)
                                            <span class="log-muted">Reason: {{ $log->reason }}</span>
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
