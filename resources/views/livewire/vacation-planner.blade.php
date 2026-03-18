<div
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
    <style>
        [x-cloak] {
            display: none !important;
        }

        .planner-wrapper {
            padding: 16px;
            max-width: 100vw;
            height: 100vh;
            display: flex;
            flex-direction: column;
            gap: 16px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            user-select: none;
            overflow: hidden;
        }

        .planner-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            position: relative;
            z-index: 80;
            overflow: visible;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            padding: 16px 24px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .planner-header-main {
            display: flex;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
            flex: 1;
        }

        .planner-intro {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 0;
        }

        .planner-intro-copy {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .planner-context {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .planner-eyebrow {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--accent-blue);
        }

        .planner-title {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1.1;
        }

        .planner-subtitle {
            font-size: 12px;
            font-weight: 500;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .planner-period {
            display: inline-flex;
            align-items: center;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(59, 130, 246, 0.08);
            color: var(--accent-blue);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: capitalize;
        }

        .planner-actions {
            display: inline-flex;
            align-items: center;
            background: #f1f5f9;
            padding: 3px;
            border-radius: 12px;
            gap: 4px;
        }

        .planner-content {
            flex: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
            gap: 16px;
            overflow: hidden;
        }

        .planner-card {
            flex: 1;
            min-height: 0;
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .planner-legend {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .planner-toolbar-summary {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .planner-secondary-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            padding: 14px 20px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.72);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 6px 12px -4px rgba(15, 23, 42, 0.1);
        }

        .planner-help {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
            white-space: nowrap;
        }

        .planner-panels {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
        }

        .planner-panel {
            background: rgba(255, 255, 255, 0.78);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08);
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .planner-panel-title {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: var(--text-main);
        }

        .planner-panel-copy {
            margin: 0;
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.5;
        }

        .request-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .request-card {
            background: rgba(248, 250, 252, 0.95);
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 14px;
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .request-card-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            flex-wrap: wrap;
        }

        .request-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .request-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-main);
            background: rgba(226, 232, 240, 0.9);
        }

        .request-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        .request-edit-form {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(148, 163, 184, 0.22);
        }

        .request-edit-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
        }

        .request-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
        }

        .request-field-full {
            grid-column: 1 / -1;
        }

        .request-input,
        .request-textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            background: white;
            color: var(--text-main);
            font: inherit;
        }

        .request-textarea {
            min-height: 96px;
            resize: vertical;
        }

        .request-helper {
            margin: 0;
            font-size: 12px;
            color: var(--text-muted);
        }

        .empty-state {
            padding: 18px;
            border-radius: 14px;
            background: rgba(248, 250, 252, 0.95);
            color: var(--text-muted);
            font-size: 13px;
            border: 1px dashed rgba(148, 163, 184, 0.3);
        }

        .row-badge {
            display: inline-flex;
            align-items: center;
            margin-left: 8px;
            padding: 4px 8px;
            border-radius: 999px;
            background: rgba(59, 130, 246, 0.14);
            color: var(--accent-blue);
            font-size: 11px;
            font-weight: 700;
        }

        .legend-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.72);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.06);
            color: var(--text-main);
            font-size: 12px;
            font-weight: 600;
        }

        .legend-code {
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.02em;
        }

        .legend-code-waiting {
            background: #d1d5db;
            color: #374151;
            border: 1px solid #9ca3af;
        }

        .chip-dot {
            width: 12px;
            height: 12px;
            border-radius: 999px;
            display: inline-block;
            flex-shrink: 0;
        }

        .dot-s { background: #4ade80; }
        .dot-fl { background: #38bdf8; }
        .dot-b { background: #facc15; }

        .grid-viewport {
            --sticky-column-offset: 360px;
            --sticky-week-row-height: 40px;
            --sticky-date-row-height: 56px;
            position: relative;
            overflow: auto;
            flex: 1;
            min-height: 0;
        }

        .month-sticky-layer {
            position: absolute;
            right: 28px;
            bottom: 28px;
            z-index: 65;
            display: flex;
            pointer-events: none;
        }

        .month-sticky-indicator {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            padding: 10px 14px;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.92), rgba(30, 41, 59, 0.88));
            border: 1px solid rgba(148, 163, 184, 0.24);
            box-shadow: 0 18px 38px rgba(15, 23, 42, 0.22);
            color: #e2e8f0;
            backdrop-filter: blur(12px);
        }

        .month-sticky-icon {
            width: 34px;
            height: 34px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(59, 130, 246, 0.18);
            color: #93c5fd;
            flex-shrink: 0;
        }

        .month-sticky-copy {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 2px;
            min-width: 0;
        }

        .month-sticky-label {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(191, 219, 254, 0.8);
        }

        .month-sticky-value {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.02em;
            color: #f8fafc;
        }

        .planner-grid {
            display: grid;
            grid-template-columns: 240px 120px repeat({{ $dates->count() }}, 44px);
            min-width: max-content;
        }

        .cell {
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            border-right: 1px solid rgba(0, 0, 0, 0.05);
            background: transparent;
            min-height: 48px;
            transition: background 0.1s ease;
        }

        .header-cell {
            position: sticky;
            top: 0;
            z-index: 20;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(4px);
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.05em;
            border-bottom: 2px solid var(--border-color);
        }

        .sticky-col {
            position: sticky;
            left: 0;
            z-index: 30;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border-right: 2px solid var(--border-color);
        }

        .sticky-col-2 {
            left: 240px;
        }

        .header-cell.sticky-col {
            z-index: 80;
        }

        .header-cell.sticky-col.sticky-col-2 {
            z-index: 79;
        }

        .week-header {
            top: 0;
            z-index: 50;
            min-height: var(--sticky-week-row-height);
            background: #f1f5f9;
            color: var(--text-main);
            font-size: 10px;
        }

        .date-header {
            top: var(--sticky-week-row-height);
            z-index: 45;
            min-height: var(--sticky-date-row-height);
            flex-direction: column;
            gap: 2px;
            line-height: 1.2;
        }

        .month-label {
            color: var(--accent-blue);
            font-weight: 700;
        }

        .department-row {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: 240px 120px repeat({{ $dates->count() }}, 44px);
            min-width: max-content;
            background: #f8fafc;
            border-bottom: 2px solid var(--border-color);
            cursor: pointer;
        }

        .department-row-label {
            grid-column: 1 / span 2;
            position: sticky;
            left: 0;
            z-index: 16;
            min-width: 360px;
            width: 360px;
            min-height: 48px;
            display: flex;
            align-items: center;
            padding: 12px 24px;
            font-weight: 600;
            font-size: 14px;
            color: var(--text-main);
            background: #f8fafc;
            border-right: 2px solid var(--border-color);
            transition: background 0.2s ease;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .department-row:hover .department-row-label,
        .department-row:hover .department-day-count {
            background: #f1f5f9;
        }

        .department-name {
            display: inline-flex;
            align-items: center;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .department-toggle-icon {
            margin-right: 8px;
            font-size: 18px;
            transition: transform 0.2s ease;
            flex-shrink: 0;
        }

        .department-row .icon {
            margin-right: 8px;
            font-size: 18px;
            flex-shrink: 0;
        }

        .department-toggle-icon.rotated {
            transform: rotate(180deg);
        }

        .department-users {
            display: grid;
            grid-template-columns: 240px 120px repeat({{ $dates->count() }}, 44px);
            min-width: max-content;
        }

        .department-day-count {
            min-height: 48px;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-main);
            background: #f8fafc;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .department-day-count-empty {
            color: var(--text-muted);
            opacity: 0.7;
        }

        .user-cell, .loc-cell {
            animation: fadeIn 0.2s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }


        .user-cell {
            justify-content: flex-start;
            font-weight: 500;
            color: var(--text-main);
            padding-left: 24px;
        }

        .current-user-spotlight {
            animation: currentUserSpotlight 1.8s ease;
        }

        @keyframes currentUserSpotlight {
            0% {
                background: rgba(59, 130, 246, 0.22);
                box-shadow: inset 0 0 0 2px rgba(59, 130, 246, 0.35);
            }
            35% {
                background: rgba(96, 165, 250, 0.28);
                box-shadow: inset 0 0 0 2px rgba(59, 130, 246, 0.55);
            }
            100% {
                background: transparent;
                box-shadow: inset 0 0 0 0 rgba(59, 130, 246, 0);
            }
        }

        .loc-cell {
            color: var(--text-muted);
            font-size: 12px;
        }

        .weekend {
            background: rgba(203, 213, 225, 0.42);
        }

        .weekend .date-label {
            color: #475569;
            font-weight: 700;
        }

        .holiday {
            background: rgba(239, 68, 68, 0.1);
        }

        .holiday .date-label {
            color: var(--accent-red);
            font-weight: 700;
        }

        /* Selection Highlight */
        .cell-selected {
            background: rgba(59, 130, 246, 0.2) !important;
            border: 1px dashed var(--accent-blue);
        }

        /* Absence Indicators */
        .absence-indicator {
            width: 100%;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 11px;
            margin: 0 -1px;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            cursor: pointer;
        }

        .absence-s { background-color: #4ade80; color: #064e3b; }
        .absence-fl { background-color: #38bdf8; color: #082f49; }
        .absence-b { background-color: #facc15; color: #422006; }
        .absence-w { background-color: #d1d5db; color: #374151; border: 1px dashed #9ca3af; }

        .absence-indicator.start { border-top-left-radius: 16px; border-bottom-left-radius: 16px; margin-left: 4px; }
        .absence-indicator.end { border-top-right-radius: 16px; border-bottom-right-radius: 16px; margin-right: 4px; }
        .absence-indicator.solo { border-radius: 16px; margin: 0 4px; }

        .cell-interactive:hover {
            background: rgba(59, 130, 246, 0.05);
            cursor: pointer;
        }

        .cell-readonly {
            cursor: default;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 32px;
            border-radius: 20px;
            width: 400px;
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

        .selection-summary {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 14px;
            border: 1px solid var(--border-color);
            border-radius: 14px;
            background: #f8fafc;
        }

        .selection-summary-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .selection-summary-title {
            color: var(--text-main);
            font-weight: 700;
        }

        .selection-summary-range {
            margin: 0;
            color: var(--text-main);
            font-size: 14px;
            font-weight: 600;
        }

        .selection-chip-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .selection-chip {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            background: white;
            border: 1px solid var(--border-color);
            color: var(--text-main);
            font-size: 12px;
            font-weight: 600;
        }

        .type-selector {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .type-btn {
            padding: 12px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            background: white;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            font-size: 12px;
        }

        .type-btn.active {
            border-color: var(--text-main);
            background: var(--text-main);
            color: white;
        }

        .reason-input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            font-family: inherit;
            font-size: 14px;
        }

        .modal-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            margin-top: 12px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-primary { background: var(--text-main); color: white; }
        .btn-secondary { background: #f1f5f9; color: var(--text-muted); }
        .btn-danger { background: #fee2e2; color: #991b1b; }

        .btn:hover { opacity: 0.9; transform: translateY(-1px); }

        .planner-toolbar {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            justify-content: flex-end;
            margin-left: auto;
            position: relative;
            z-index: 90;
        }

        .filter-search {
            min-width: 280px;
            padding: 10px 14px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.9);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.06);
        }

        .filter-search .icon {
            color: var(--text-muted);
            font-size: 18px;
            flex-shrink: 0;
        }

        .filter-search-input {
            width: 100%;
            border: none;
            outline: none;
            background: transparent;
            color: var(--text-main);
            font-size: 13px;
            font-weight: 500;
            padding: 0;
        }

        .filter-search-input::placeholder {
            color: var(--text-muted);
        }

        .filter-dropdown {
            position: relative;
            z-index: 95;
        }

        .filter-trigger {
            min-width: 220px;
            padding: 10px 14px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.9);
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.06);
        }

        .filter-trigger-copy {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 2px;
            min-width: 0;
            text-align: left;
        }

        .filter-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .filter-value {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: var(--text-main);
            font-size: 13px;
            font-weight: 600;
        }

        .filter-panel {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            width: 280px;
            max-height: 320px;
            overflow: auto;
            padding: 12px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.98);
            border: 1px solid rgba(148, 163, 184, 0.2);
            box-shadow: 0 18px 35px rgba(15, 23, 42, 0.16);
            z-index: 120;
        }

        .filter-panel-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            gap: 8px;
        }

        .filter-link {
            border: none;
            background: transparent;
            color: var(--accent-blue);
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            padding: 0;
        }

        .filter-options {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .filter-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.15s ease;
            color: var(--text-main);
            font-size: 13px;
            font-weight: 500;
        }

        .filter-option:hover {
            background: #f8fafc;
        }

        .filter-option input {
            margin: 0;
            width: 16px;
            height: 16px;
        }

        @media (max-width: 1100px) {
            .planner-header {
                align-items: flex-start;
            }

            .planner-toolbar {
                width: 100%;
                margin-left: 0;
                justify-content: flex-start;
            }

            .filter-search {
                width: 100%;
                min-width: 0;
            }

            .planner-actions {
                margin-left: 0;
            }

            .planner-secondary-bar {
                align-items: flex-start;
            }

            .planner-toolbar-summary {
                width: 100%;
            }

            .planner-help {
                white-space: normal;
            }
        }
    </style>

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
                absenceType: initialAbsenceType,
                reason: '',
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
