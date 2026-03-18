<div
    class="planner-root"
    style="--planner-date-count: {{ $dates->count() }};"
    wire:key="planner-{{ md5($viewDate . '|' . json_encode($selectedDepartments) . '|' . json_encode($selectedSites) . '|' . json_encode($selectedManagers) . '|' . $search . '|' . ($currentUser?->id ?? 'guest')) }}"
    x-data="planner({
        initialViewDate: @js($viewDate),
        initialDepartments: @js($departments->pluck('name')->values()),
        availableDepartments: @js($allDepartments->values()),
        availableSites: @js($sites->values()),
        availableManagers: @js($managers->values()),
        editableUserId: @js($currentUser?->id),
        initialAbsenceType: @js($absenceType),
        selectedDepartments: @entangle('selectedDepartments').live,
        selectedSites: @entangle('selectedSites').live,
        selectedManagers: @entangle('selectedManagers').live
    })"
    @mouseup.window="stopDragging()"
>
    <div class="planner-wrapper">
        <header class="planner-header">
            <div class="planner-header-main">
                <div class="planner-intro">
                    <div class="planner-intro-copy">
                        <span class="planner-eyebrow">Absence planner</span>
                        <h1 class="planner-title">Team timeline</h1>
                        <span class="planner-subtitle">Review availability, filter teams, and drag across days to plan leave without jumping between views.</span>
                    </div>

                    <div class="planner-context">
                        <div class="planner-period">{{ $periodLabel }}</div>
                    </div>
                </div>
                
            </div>

            <div class="planner-toolbar">
                <label class="filter-search" aria-label="Search personnel">
                    <span class="icon">search</span>
                    <input
                        type="search"
                        class="filter-search-input"
                        wire:model.live.debounce.300ms="search"
                        x-on:input="departmentFilterOpen = false; siteFilterOpen = false; managerFilterOpen = false"
                        placeholder="Search personnel"
                    >
                </label>

                <div class="filter-dropdown" @click.outside="departmentFilterOpen = false">
                    <button type="button" class="filter-trigger" @click="departmentFilterOpen = !departmentFilterOpen; siteFilterOpen = false; managerFilterOpen = false">
                        <span class="filter-trigger-copy">
                            <span class="filter-label">Departments</span>
                            <span class="filter-value" x-text="filterLabel(selectedDepartments, 'All Departments')"></span>
                        </span>
                        <span class="icon">expand_more</span>
                    </button>

                    <div class="filter-panel" x-show="departmentFilterOpen" x-cloak x-transition.origin.top.right>
                        <div class="filter-panel-actions">
                            <strong style="font-size: 12px; color: var(--text-main);">Choose departments</strong>
                            <div style="display: inline-flex; gap: 12px;">
                                <button type="button" class="filter-link" @click="selectedDepartments = []">Clear</button>
                                <button type="button" class="filter-link" @click="selectedDepartments = [...availableDepartments]">All</button>
                            </div>
                        </div>

                        <div class="filter-options">
                            <template x-for="department in availableDepartments" :key="department">
                                <label class="filter-option">
                                    <input type="checkbox" x-model="selectedDepartments" :value="department">
                                    <span x-text="department"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="filter-dropdown" @click.outside="managerFilterOpen = false">
                    <button type="button" class="filter-trigger" @click="managerFilterOpen = !managerFilterOpen; departmentFilterOpen = false; siteFilterOpen = false">
                        <span class="filter-trigger-copy">
                            <span class="filter-label">Managers</span>
                            <span class="filter-value" x-text="managerFilterLabel()"></span>
                        </span>
                        <span class="icon">expand_more</span>
                    </button>

                    <div class="filter-panel" x-show="managerFilterOpen" x-cloak x-transition.origin.top.right>
                        <div class="filter-panel-actions">
                            <strong style="font-size: 12px; color: var(--text-main);">Choose managers</strong>
                            <div style="display: inline-flex; gap: 12px;">
                                <button type="button" class="filter-link" @click="selectedManagers = []">Clear</button>
                                <button type="button" class="filter-link" @click="selectedManagers = availableManagers.map((manager) => manager.id)">All</button>
                            </div>
                        </div>

                        <div class="filter-options">
                            <template x-for="manager in availableManagers" :key="manager.id">
                                <label class="filter-option">
                                    <input type="checkbox" x-model="selectedManagers" :value="manager.id">
                                    <span x-text="manager.name"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="filter-dropdown" @click.outside="siteFilterOpen = false">
                    <button type="button" class="filter-trigger" @click="siteFilterOpen = !siteFilterOpen; departmentFilterOpen = false; managerFilterOpen = false">
                        <span class="filter-trigger-copy">
                            <span class="filter-label">Sites</span>
                            <span class="filter-value" x-text="filterLabel(selectedSites, 'All Sites')"></span>
                        </span>
                        <span class="icon">expand_more</span>
                    </button>

                    <div class="filter-panel" x-show="siteFilterOpen" x-cloak x-transition.origin.top.right>
                        <div class="filter-panel-actions">
                            <strong style="font-size: 12px; color: var(--text-main);">Choose sites</strong>
                            <div style="display: inline-flex; gap: 12px;">
                                <button type="button" class="filter-link" @click="selectedSites = []">Clear</button>
                                <button type="button" class="filter-link" @click="selectedSites = [...availableSites]">All</button>
                            </div>
                        </div>

                        <div class="filter-options">
                            <template x-for="site in availableSites" :key="site">
                                <label class="filter-option">
                                    <input type="checkbox" x-model="selectedSites" :value="site">
                                    <span x-text="site"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>

            </div>
        </header>

        <div class="planner-secondary-bar">
            <div class="planner-toolbar-summary">
                <div class="planner-help">
                    Drag across days to select range
                </div>

                <div class="planner-legend" aria-label="Absence colors">
                    @foreach($absenceOptions as $option)
                        <span class="legend-item">
                            <span class="chip-dot" style="background: {{ $option->color }};"></span>
                            {{ $option->label }}
                        </span>
                    @endforeach
                    <span class="legend-item">
                        <span class="legend-code legend-code-waiting">W</span>
                        Waiting approval
                    </span>
                </div>
            </div>

            <div class="planner-actions">
                @if ($currentUser)
                    <button type="button" class="btn btn-secondary" style="padding: 6px 16px; font-size: 13px;" @click="scrollToCurrentUser({ smooth: true, highlight: true })">
                        Me
                    </button>
                @endif
                <button type="button" wire:click="previousYear" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;">
                    −1Y
                </button>
                <button type="button" wire:click="previousMonth" class="btn btn-secondary" style="padding: 6px 12px;">
                    <span class="icon" style="font-size: 18px;">chevron_left</span>
                </button>
                <button type="button" wire:click="goToToday" class="btn btn-secondary" style="padding: 6px 16px; font-size: 13px;">
                    Today
                </button>
                <button type="button" wire:click="nextMonth" class="btn btn-secondary" style="padding: 6px 12px;">
                    <span class="icon" style="font-size: 18px;">chevron_right</span>
                </button>
                <button type="button" wire:click="nextYear" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;">
                    +1Y
                </button>
            </div>
        </div>

        <div class="planner-content">
            @if ($currentUser)
                <section class="planner-mobile-entry" x-cloak>
                    <div class="planner-mobile-entry-head">
                        <div>
                            <h2 class="planner-mobile-entry-title">Add absence</h2>
                            <p class="planner-mobile-entry-copy">When the planner grid is hidden on narrow screens, use a date span instead.</p>
                        </div>

                        <button
                            type="button"
                            class="btn btn-primary planner-mobile-trigger"
                            @click="toggleMobileQuickAdd()"
                            x-text="showMobileQuickAdd ? 'Close form' : 'Add absence'"
                        ></button>
                    </div>

                    <div class="planner-mobile-form" x-show="showMobileQuickAdd" x-transition.opacity.duration.150ms>
                        <div class="request-edit-grid">
                            <label class="request-field">
                                <span>Start date</span>
                                <input type="date" class="request-input" x-model="mobileStartDate">
                            </label>

                            <label class="request-field">
                                <span>End date</span>
                                <input type="date" class="request-input" x-model="mobileEndDate">
                            </label>

                            <label class="request-field request-field-full">
                                <span>Absence type</span>
                                <select class="request-input" x-model="mobileAbsenceType">
                                    @foreach($absenceOptions as $absenceOption)
                                        <option value="{{ $absenceOption->code }}">{{ $absenceOption->label }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="request-field request-field-full">
                                <span>Reason</span>
                                <textarea class="request-textarea" x-model="mobileReason" rows="3" placeholder="Add a reason (optional)..."></textarea>
                            </label>
                        </div>

                        <div class="selection-summary" x-show="mobileDateSpanLabel">
                            <div class="selection-summary-header">
                                <span class="selection-summary-title">Selected span</span>
                                <span x-text="mobileDateSpanLabel"></span>
                            </div>
                        </div>

                        <div class="request-actions">
                            <button type="button" class="btn btn-secondary" @click="resetMobileQuickAdd()">Cancel</button>
                            <button type="button" class="btn btn-primary" :disabled="!mobileQuickAddReady" @click="submitMobileQuickAdd()">Apply absence</button>
                        </div>
                    </div>
                </section>
            @endif

            @if($pendingRequests->isNotEmpty() || $managerApprovals->isNotEmpty())
                <div class="planner-panels">
                    @if($pendingRequests->isNotEmpty())
                        <section class="planner-panel">
                            <div>
                                <h2 class="planner-panel-title">Your pending requests</h2>
                                <p class="planner-panel-copy">Requests only become approved automatically when you do not have a manager.</p>
                            </div>

                            <div class="request-list">
                                @foreach($pendingRequests as $request)
                                    @php
                                        $option = $absenceOptionsByCode->get($request['type']);
                                        $isEditing = $editingRequestUuid === $request['request_uuid'];
                                    @endphp
                                    <article class="request-card" wire:key="pending-request-{{ $request['request_uuid'] }}">
                                        <div class="request-card-head">
                                            <div>
                                                <strong>{{ $option?->label ?? $request['type'] }}</strong>
                                                <div style="margin-top: 4px; color: var(--text-muted); font-size: 13px;">{{ $request['date_label'] }} · {{ $request['date_count'] }} day(s)</div>
                                            </div>
                                            <span class="request-pill">
                                                <span class="chip-dot" style="background: {{ $option?->color ?? '#94a3b8' }};"></span>
                                                Waiting approval
                                            </span>
                                        </div>

                                        @if($request['reason'])
                                            <div style="font-size: 13px; color: var(--text-main);">{{ $request['reason'] }}</div>
                                        @endif

                                        @if($isEditing)
                                            <div class="request-edit-form">
                                                <p class="request-helper">Adjust the date range, absence type, or reason before your manager reviews this request.</p>

                                                <div class="request-edit-grid">
                                                    <label class="request-field">
                                                        <span>Start date</span>
                                                        <input type="date" class="request-input" wire:model.live="editingRequestStartDate">
                                                    </label>

                                                    <label class="request-field">
                                                        <span>End date</span>
                                                        <input type="date" class="request-input" wire:model.live="editingRequestEndDate">
                                                    </label>

                                                    <label class="request-field request-field-full">
                                                        <span>Absence type</span>
                                                        <select class="request-input" wire:model.live="editingRequestType">
                                                            @foreach($absenceOptions as $absenceOption)
                                                                <option value="{{ $absenceOption->code }}">{{ $absenceOption->label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </label>

                                                    <label class="request-field request-field-full">
                                                        <span>Reason</span>
                                                        <textarea class="request-textarea" wire:model.live="editingRequestReason" rows="3" placeholder="Add a reason (optional)..."></textarea>
                                                    </label>
                                                </div>

                                                <div class="request-actions">
                                                    <button type="button" class="btn btn-secondary" wire:click="cancelEditingRequest">Cancel</button>
                                                    <button type="button" class="btn btn-danger" wire:click="deletePendingRequest('{{ $request['request_uuid'] }}')">Delete request</button>
                                                    <button type="button" class="btn btn-primary" wire:click="updatePendingRequest">Save changes</button>
                                                </div>
                                            </div>
                                        @else
                                            <div class="request-actions">
                                                <button type="button" class="btn btn-secondary" wire:click="startEditingRequest('{{ $request['request_uuid'] }}')">Edit</button>
                                                <button type="button" class="btn btn-danger" wire:click="deletePendingRequest('{{ $request['request_uuid'] }}')">Delete</button>
                                            </div>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if($managerApprovals->isNotEmpty())
                        <section class="planner-panel">
                            <div>
                                <h2 class="planner-panel-title">Approvals waiting for you</h2>
                                <p class="planner-panel-copy">Approve or reject requests from users who report to you.</p>
                            </div>

                            <div class="request-list">
                                @foreach($managerApprovals as $request)
                                    @php
                                        $option = $absenceOptionsByCode->get($request['type']);
                                    @endphp
                                    <article class="request-card">
                                        <div class="request-card-head">
                                            <div>
                                                <strong>{{ $request['user_name'] }}</strong>
                                                <div style="margin-top: 4px; color: var(--text-muted); font-size: 13px;">{{ $option?->label ?? $request['type'] }} · {{ $request['date_label'] }} · {{ $request['date_count'] }} day(s)</div>
                                            </div>
                                            <span class="request-pill">
                                                <span class="chip-dot" style="background: {{ $option?->color ?? '#94a3b8' }};"></span>
                                                Pending
                                            </span>
                                        </div>

                                        @if($request['reason'])
                                            <div style="font-size: 13px; color: var(--text-main);">{{ $request['reason'] }}</div>
                                        @endif

                                        <div class="request-actions">
                                            <button type="button" class="btn btn-secondary" wire:click="rejectRequest('{{ $request['request_uuid'] }}')">Reject</button>
                                            <button type="button" class="btn btn-primary" wire:click="approveRequest('{{ $request['request_uuid'] }}')">Approve</button>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>
            @endif


            <main class="planner-card">
                <div class="grid-viewport">
                    <div class="planner-grid">
                    <!-- Headers -->
                    <div class="cell header-cell sticky-col" style="grid-row: 1 / 3;"><span>Personnel</span></div>
                    <div class="cell header-cell sticky-col sticky-col-2" style="grid-row: 1 / 3;"><span>Site</span></div>

                    @foreach($weeks as $weekKey => $weekDates)
                        <div class="cell header-cell week-header" style="grid-column: span {{ $weekDates->count() }};">
                            {{ $weekDates->first()['week_year'] }} · W{{ $weekDates->first()['week'] }}
                        </div>
                    @endforeach

                    @foreach($dates as $index => $date)
                        <div class="cell header-cell date-header date-header-data 
                             {{ $date['is_weekend'] ? 'weekend' : '' }} 
                             {{ $date['is_holiday'] ? 'holiday' : '' }}" 
                             data-date="{{ $date['date'] }}"
                                data-month-label="{{ \Carbon\Carbon::parse($date['date'])->translatedFormat('F Y') }}"
                             @if($date['date'] === date('Y-m-d')) id="today" @endif
                             title="{{ $date['holiday_name'] ?? ($date['is_weekend'] ? 'Weekend' : '') }}">

                            @if($date['day'] == 1 || $loop->first)
                                <span class="month-label">{{ $date['month'] }}</span>
                            @endif
                            <span class="date-label">{{ $date['day'] }}</span>
                        </div>
                    @endforeach

                    <!-- Data Rows -->
                    @foreach($departments as $dept)
                        <div class="department-row" @click="toggleDept('{{ $dept->name }}')">
                            <div class="department-row-label">
                                <span class="icon department-toggle-icon" :class="{ 'rotated': isDepartmentExpanded('{{ $dept->name }}') }">expand_more</span>
                                <span class="icon" style="margin-right: 8px; font-size: 18px;">business</span>
                                <span class="department-name">{{ $dept->name }}</span>
                            </div>

                            @foreach($dates as $date)
                                @php
                                    $departmentDayCount = $dept->day_counts->get($date['date'], 0);
                                @endphp
                                <div class="cell department-day-count {{ $date['is_weekend'] ? 'weekend' : '' }} {{ $date['is_holiday'] ? 'holiday' : '' }}"
                                     title="{{ $departmentDayCount === 1 ? '1 person has leave on this day' : $departmentDayCount . ' people have leave on this day' }}">
                                    <span class="{{ $departmentDayCount === 0 ? 'department-day-count-empty' : '' }}">
                                        {{ $departmentDayCount }}
                                    </span>
                                </div>
                            @endforeach
                        </div>

                        <div class="department-users" x-show="isDepartmentExpanded('{{ $dept->name }}')" x-collapse>
                            @foreach($dept->users as $user)
                                <div class="cell sticky-col user-cell" @if($currentUser?->id === $user->id) data-current-user-row="true" data-current-user-cell="true" @endif>
                                    {{ $user->name }}
                                    @if($currentUser?->id === $user->id)
                                        <span class="row-badge">You</span>
                                    @endif
                                </div>
                                <div class="cell sticky-col sticky-col-2 loc-cell" @if($currentUser?->id === $user->id) data-current-user-cell="true" @endif>{{ $user->location }}</div>
                                
                                @php
                                    $userAbsences = $user->absences->keyBy('date');
                                @endphp

                                @foreach($dates as $index => $date)
                                    @php
                                        $absence = $userAbsences->get($date['date']);
                                        $prevDate = \Carbon\Carbon::parse($date['date'])->subDay()->format('Y-m-d');
                                        $nextDate = \Carbon\Carbon::parse($date['date'])->addDay()->format('Y-m-d');

                                        $isCurrent = !!$absence;
                                        $type = $absence?->type;
                                        $isWaiting = $absence?->status === \App\Models\Absence::STATUS_PENDING;
                                        $absenceCode = $isWaiting ? 'W' : $type;
                                        $absenceClass = $isWaiting ? 'w' : strtolower((string) $type);
                                        $absenceGroup = $isCurrent ? implode('|', [$absence->status, $type]) : null;
                                        $prevAbsence = $userAbsences->get($prevDate);
                                        $nextAbsence = $userAbsences->get($nextDate);
                                        $hasPrev = $isCurrent && $prevAbsence && implode('|', [$prevAbsence->status, $prevAbsence->type]) === $absenceGroup;
                                        $hasNext = $isCurrent && $nextAbsence && implode('|', [$nextAbsence->status, $nextAbsence->type]) === $absenceGroup;
                                        $absenceLabel = $absenceOptionsByCode->get($type)?->label ?? $type;

                                        $isSolo = $isCurrent && !$hasPrev && !$hasNext;
                                        $isStart = $isCurrent && !$hasPrev && $hasNext;
                                        $isEnd = $isCurrent && $hasPrev && !$hasNext;
                                        $isMid = $isCurrent && $hasPrev && $hasNext;
                                    @endphp
                                    <div class="cell {{ $currentUser?->id === $user->id ? 'cell-interactive' : 'cell-readonly' }}
                                         {{ $date['is_weekend'] ? 'weekend' : '' }}
                                         {{ $date['is_holiday'] ? 'holiday' : '' }}"
                                         :class="{ 'cell-selected': isSelected({{ $user->id }}, {{ $index }}) }"
                                         @if($currentUser?->id === $user->id) data-current-user-cell="true" @endif
                                         @if($currentUser?->id === $user->id)
                                         @mousedown="startDragging($event, {{ $user->id }}, {{ $index }})"
                                         @mouseenter="dragEnter({{ $index }})"
                                         @endif
                                         title="{{ $date['holiday_name'] ?? '' }}">
                                        @if($isCurrent)
                                            <div class="absence-indicator absence-{{ $absenceClass }}
                                                {{ $isSolo ? 'solo' : '' }}
                                                {{ $isStart ? 'start' : '' }}
                                                {{ $isEnd ? 'end' : '' }}"
                                                title="{{ $isWaiting ? 'Waiting approval' : 'Approved' }}{{ $absenceLabel ? ': ' . $absenceLabel : '' }}{{ $absence->reason ? ' — ' . $absence->reason : '' }}"
                                                @if(!$isWaiting)
                                                style="background-color: {{ $absenceOptionsByCode->get($type)?->color ?? '#94a3b8' }}; color: #0f172a;"
                                                @endif>
                                                @if($isSolo || $isStart)
                                                    {{ $absenceCode }}
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    @endforeach
                </div>

            </div>

            <div class="month-sticky-layer" x-show="currentVisibleMonthLabel" x-cloak>
                <div class="month-sticky-indicator">
                    <span class="icon month-sticky-icon">calendar_month</span>
                    <span class="month-sticky-copy">
                        <span class="month-sticky-label">Visible month</span>
                        <span class="month-sticky-value" x-text="currentVisibleMonthLabel"></span>
                    </span>
                </div>
            </div>
        </main>
        </div>
    </div>

    <!-- Selection Modal -->
    <div class="modal-overlay" x-show="showModal" x-cloak x-transition>
        <div class="modal-content" @click.away="reset()">
            <h2 class="modal-title">Define Absence</h2>

            <div class="selection-summary" x-show="selectedDates.length > 0">
                <div class="selection-summary-header">
                    <span class="selection-summary-title">Selected days</span>
                    <span x-text="selectionStatsLabel"></span>
                </div>

                <div class="selection-chip-list">
                    <template x-for="span in selectedDateSpans" :key="span.key">
                        <span class="selection-chip" x-text="span.label"></span>
                    </template>
                </div>
            </div>
            
            <div class="type-selector">
                @foreach($absenceOptions as $option)
                    <button class="type-btn" :class="{ 'active': absenceType === '{{ $option->code }}' }" @click="absenceType = '{{ $option->code }}'">
                        <span class="chip-dot" style="background: {{ $option->color }};"></span> {{ $option->label }}
                    </button>
                @endforeach
            </div>

            <textarea class="reason-input" x-model="reason" placeholder="Add a reason (optional)..." rows="3"></textarea>

            <div class="modal-actions">
                <button class="btn btn-secondary" @click="reset()">Cancel</button>
                <button class="btn btn-danger" @click="remove()">Clear Selection</button>
                <button class="btn btn-primary" @click="apply()">Apply Absence</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('planner', ({
                initialViewDate,
                initialDepartments = [],
                availableDepartments = [],
                availableSites = [],
                availableManagers = [],
                editableUserId = null,
                initialAbsenceType = null,
                selectedDepartments,
                selectedSites,
                selectedManagers,
            }) => ({
                initialViewDate,
                editableUserId,
                currentUserHighlightTimeout: null,
                currentVisibleMonthLabel: '',
                isDragging: false,
                selectionStart: null,
                selectionEnd: null,
                selectedUser: null,
                showModal: false,
                showMobileQuickAdd: false,
                absenceType: initialAbsenceType,
                reason: '',
                mobileStartDate: '',
                mobileEndDate: '',
                mobileAbsenceType: initialAbsenceType,
                mobileReason: '',
                availableDepartments,
                availableSites,
                availableManagers,
                selectedDepartments,
                selectedSites,
                selectedManagers,
                departmentFilterOpen: false,
                siteFilterOpen: false,
                managerFilterOpen: false,
                expandedDepartments: new Set(initialDepartments),

                init() {
                    this.$nextTick(() => {
                        const viewport = this.$root.querySelector('.grid-viewport');

                        if (viewport) {
                            viewport.addEventListener('scroll', () => this.syncVisibleMonth(), { passive: true });
                        }

                        this.scrollToCurrentUser();
                        this.syncVisibleMonth();
                    });
                },

                filterLabel(values, fallback) {
                    if (!Array.isArray(values) || values.length === 0) {
                        return fallback;
                    }

                    if (values.length === 1) {
                        return values[0];
                    }

                    return `${values.length} selected`;
                },

                managerFilterLabel() {
                    if (!Array.isArray(this.selectedManagers) || this.selectedManagers.length === 0) {
                        return 'All Managers';
                    }

                    if (this.selectedManagers.length === 1) {
                        const selectedManager = this.availableManagers.find((manager) => manager.id === this.selectedManagers[0]);

                        return selectedManager?.name ?? '1 selected';
                    }

                    return `${this.selectedManagers.length} selected`;
                },

                scrollToDate(date, smooth = false) {
                    const viewport = this.$root.querySelector('.grid-viewport');
                    const target = this.$root.querySelector(`.date-header-data[data-date="${date}"]`) ?? this.$root.querySelector('#today');

                    if (!viewport || !target) {
                        return;
                    }

                    const left = Math.max(target.offsetLeft - 400, 0);

                    if (smooth) {
                        viewport.scrollTo({
                            left,
                            behavior: 'smooth',
                        });

                        window.requestAnimationFrame(() => this.syncVisibleMonth());

                        return;
                    }

                    viewport.scrollLeft = left;
                    this.syncVisibleMonth();
                },

                syncVisibleMonth() {
                    const viewport = this.$root.querySelector('.grid-viewport');
                    const dateHeaders = Array.from(this.$root.querySelectorAll('.date-header-data'));

                    if (!viewport || !dateHeaders.length) {
                        this.currentVisibleMonthLabel = '';
                        return;
                    }

                    const stickyOffset = 360;
                    const probePoint = viewport.scrollLeft + stickyOffset + 22;

                    const activeHeader = dateHeaders.find((header) => {
                        const leftEdge = header.offsetLeft;
                        const rightEdge = leftEdge + header.offsetWidth;

                        return probePoint >= leftEdge && probePoint < rightEdge;
                    })
                        ?? dateHeaders.find((header) => (header.offsetLeft + header.offsetWidth) > (viewport.scrollLeft + stickyOffset))
                        ?? dateHeaders[0];

                    this.currentVisibleMonthLabel = activeHeader?.dataset.monthLabel ?? '';
                },

                highlightCurrentUserRow() {
                    const currentUserCells = this.$root.querySelectorAll('[data-current-user-cell="true"]');

                    if (!currentUserCells.length) {
                        return;
                    }

                    currentUserCells.forEach((cell) => {
                        cell.classList.remove('current-user-spotlight');
                        void cell.offsetWidth;
                        cell.classList.add('current-user-spotlight');
                    });

                    if (this.currentUserHighlightTimeout) {
                        clearTimeout(this.currentUserHighlightTimeout);
                    }

                    this.currentUserHighlightTimeout = window.setTimeout(() => {
                        currentUserCells.forEach((cell) => cell.classList.remove('current-user-spotlight'));
                        this.currentUserHighlightTimeout = null;
                    }, 1900);
                },

                scrollToCurrentUser({ smooth = false, highlight = false } = {}) {
                    this.$nextTick(() => {
                        this.scrollToDate(this.initialViewDate, smooth);

                        const viewport = this.$root.querySelector('.grid-viewport');
                        const currentUserRow = this.$root.querySelector('[data-current-user-row="true"]');

                        if (!viewport || !currentUserRow) {
                            return;
                        }

                        const top = Math.max(currentUserRow.offsetTop - 180, 0);

                        if (smooth) {
                            viewport.scrollTo({
                                top,
                                behavior: 'smooth',
                            });
                        } else {
                            viewport.scrollTop = top;
                        }

                        if (highlight) {
                            window.setTimeout(() => this.highlightCurrentUserRow(), smooth ? 320 : 0);
                        }
                    });
                },

                toggleDepartment(deptName) {
                    if (this.expandedDepartments.has(deptName)) {
                        this.expandedDepartments.delete(deptName);
                    } else {
                        this.expandedDepartments.add(deptName);
                    }
                },

                isDepartmentExpanded(deptName) {
                    return this.expandedDepartments.has(deptName);
                },

                startDragging(event, userId, dateIndex) {
                    if (event.button !== 0) return;
                    if (this.editableUserId === null || this.editableUserId !== userId) return;

                    this.isDragging = true;
                    this.selectedUser = userId;
                    this.selectionStart = dateIndex;
                    this.selectionEnd = dateIndex;
                },

                dragEnter(dateIndex) {
                    if (!this.isDragging) return;
                    this.selectionEnd = dateIndex;
                },

                stopDragging() {
                    if (!this.isDragging) return;
                    this.isDragging = false;
                    this.showModal = true;
                },

                get selectedRange() {
                    if (this.selectionStart === null || this.selectionEnd === null) return [];
                    const start = Math.min(this.selectionStart, this.selectionEnd);
                    const end = Math.max(this.selectionStart, this.selectionEnd);
                    return Array.from({ length: end - start + 1 }, (_, i) => start + i);
                },

                isSelected(userId, dateIndex) {
                    return this.selectedUser === userId && this.selectedRange.includes(dateIndex);
                },

                dateHeaders() {
                    return Array.from(this.$root.querySelectorAll('.date-header-data'));
                },

                formatSelectionDate(isoDate) {
                    const [year, month, day] = isoDate.split('-').map(Number);
                    const date = new Date(Date.UTC(year, month - 1, day));

                    return new Intl.DateTimeFormat('en-GB', {
                        weekday: 'short',
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric',
                        timeZone: 'UTC',
                    }).format(date);
                },

                get selectedDates() {
                    return this.selectedRange
                        .map((idx) => this.dateHeaders()[idx]?.dataset.date ?? null)
                        .filter((date) => Boolean(date))
                        .map((iso) => ({
                            iso,
                            isWeekend: [0, 6].includes(new Date(`${iso}T00:00:00Z`).getUTCDay()),
                            label: this.formatSelectionDate(iso),
                        }));
                },

                get selectedWeekendDays() {
                    return this.selectedDates.filter((date) => date.isWeekend).length;
                },

                get selectedDateSpans() {
                    if (this.selectedDates.length === 0) {
                        return [];
                    }

                    const spans = [];

                    this.selectedDates.forEach((date) => {
                        const currentDate = new Date(`${date.iso}T00:00:00Z`);
                        const previousSpan = spans[spans.length - 1];

                        if (!previousSpan) {
                            spans.push({
                                key: date.iso,
                                startIso: date.iso,
                                endIso: date.iso,
                                startLabel: date.label,
                                endLabel: date.label,
                            });

                            return;
                        }

                        const previousEndDate = new Date(`${previousSpan.endIso}T00:00:00Z`);
                        const dayDiff = (currentDate.getTime() - previousEndDate.getTime()) / 86400000;

                        if (dayDiff === 1) {
                            previousSpan.endIso = date.iso;
                            previousSpan.endLabel = date.label;

                            return;
                        }

                        spans.push({
                            key: date.iso,
                            startIso: date.iso,
                            endIso: date.iso,
                            startLabel: date.label,
                            endLabel: date.label,
                        });
                    });

                    return spans.map((span) => ({
                        key: span.key,
                        label: span.startIso === span.endIso
                            ? span.startLabel
                            : `${span.startLabel} to ${span.endLabel}`,
                    }));
                },

                get selectionStatsLabel() {
                    const totalDaysLabel = this.selectedDates.length === 1 ? '1 day' : `${this.selectedDates.length} days`;
                    const weekendDaysLabel = this.selectedWeekendDays === 1 ? '1 weekend day' : `${this.selectedWeekendDays} weekend days`;

                    return `${totalDaysLabel} | ${weekendDaysLabel}`;
                },

                get mobileQuickAddReady() {
                    return Boolean(this.editableUserId && this.mobileStartDate && this.mobileEndDate && this.mobileAbsenceType);
                },

                get mobileDateSpanLabel() {
                    if (!this.mobileStartDate || !this.mobileEndDate) {
                        return '';
                    }

                    const startLabel = this.formatSelectionDate(this.mobileStartDate);
                    const endLabel = this.formatSelectionDate(this.mobileEndDate);

                    return this.mobileStartDate === this.mobileEndDate
                        ? startLabel
                        : `${startLabel} to ${endLabel}`;
                },

                apply() {
                    if (this.selectedUser === null) return;
                    const dates = this.selectedDates.map((date) => date.iso);
                    this.$wire.applyAbsence(this.selectedUser, dates, this.absenceType, this.reason);
                    this.reset();
                },

                remove() {
                    if (this.selectedUser === null) return;
                    const dates = this.selectedDates.map((date) => date.iso);
                    this.$wire.removeAbsence(this.selectedUser, dates);
                    this.reset();
                },

                toggleMobileQuickAdd() {
                    if (this.showMobileQuickAdd) {
                        this.resetMobileQuickAdd();

                        return;
                    }

                    this.showMobileQuickAdd = true;
                },

                submitMobileQuickAdd() {
                    if (!this.mobileQuickAddReady || this.editableUserId === null) {
                        return;
                    }

                    this.$wire.applyAbsenceSpan(
                        this.editableUserId,
                        this.mobileStartDate,
                        this.mobileEndDate,
                        this.mobileAbsenceType,
                        this.mobileReason,
                    );

                    this.resetMobileQuickAdd();
                },

                resetMobileQuickAdd() {
                    this.showMobileQuickAdd = false;
                    this.mobileStartDate = '';
                    this.mobileEndDate = '';
                    this.mobileReason = '';
                    this.mobileAbsenceType = this.absenceType ?? this.mobileAbsenceType;
                },

                reset() {
                    this.showModal = false;
                    this.selectionStart = null;
                    this.selectionEnd = null;
                    this.selectedUser = null;
                    this.reason = '';
                },

                // This function will be called from the HTML to toggle department visibility
                toggleDept(deptName) {
                    this.toggleDepartment(deptName);
                }
            }));
        });
    </script>
</div>
