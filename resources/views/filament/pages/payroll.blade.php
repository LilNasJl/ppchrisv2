<x-filament-panels::page>
    @php
        $periods = $this->payrollPeriods();
        $branches = $this->branches();
        $selectedPeriod = $this->selectedPeriod();
        $selectedBranch = $this->selectedBranch();
        $rows = $this->filteredEmployeeRows();
    @endphp

    <style>
        .payroll-workspace {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            width: 100%;
        }

        .payroll-topbar {
            position: relative;
            z-index: 20;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem 1.5rem;
            padding: 1.25rem 1.5rem;
            border-radius: 0.75rem;
            border: 1px solid #3b82f6;
            background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50);
            color: #ffffff;
        }

        .payroll-topbar-copy {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            min-width: 0;
        }

        .payroll-eyebrow {
            margin: 0;
            color: #d9f722;
            font-size: 0.7rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .payroll-title {
            margin: 0;
            color: #ffffff;
            font-size: 1.65rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .payroll-subtitle {
            margin: 0;
            color: #e0ecff;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .payroll-actions-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        .payroll-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 0.85rem;
            border-radius: 0.5rem;
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
            color: #ffffff;
            background: rgb(37, 99, 235);
            border: 1px solid transparent;
            transition: background-color 150ms ease;
            cursor: pointer;
            white-space: nowrap;
        }

        .payroll-btn:hover {
            background: rgb(29, 78, 216);
        }

        .payroll-btn.secondary {
            background: rgb(15, 23, 42);
        }

        .dark .payroll-btn.secondary {
            background: rgb(30, 64, 175);
        }

        .payroll-btn.outline {
            color: rgb(30, 41, 59);
            background: rgb(248, 250, 252);
            border-color: rgb(203, 213, 225);
        }

        .dark .payroll-btn.outline {
            color: rgb(226, 232, 240);
            background: rgb(30, 41, 59);
            border-color: rgb(71, 85, 105);
        }

        .payroll-topbar .payroll-btn {
            min-height: 2.25rem;
            padding: 0.35rem 0.7rem;
            border-radius: 0.45rem;
            font-size: 0.75rem;
        }

        .payroll-topbar .payroll-btn.outline,
        .payroll-topbar .payroll-btn.secondary {
            color: #ffffff;
            background: rgb(10 31 68 / 0.6);
            border-color: rgb(147 197 253 / 0.35);
        }

        .payroll-topbar .payroll-btn.outline:hover,
        .payroll-topbar .payroll-btn.secondary:hover {
            color: #ffffff;
            background: rgb(10 31 68 / 0.85);
            border-color: rgb(191 219 254 / 0.65);
        }

        .payroll-btn-icon {
            width: 1rem;
            height: 1rem;
            flex: none;
        }

        .payroll-filter-card {
            display: grid;
            grid-template-columns: minmax(13rem, 1.2fr) minmax(11rem, 1fr) minmax(13rem, 1.2fr) auto;
            gap: 0.65rem 0.75rem;
            align-items: end;
            padding: 0.75rem 1rem;
            border-radius: 0.65rem;
            border: 1px solid rgb(226, 232, 240);
            background: #ffffff;
        }

        .dark .payroll-filter-card {
            border-color: rgb(51, 65, 85);
            background: rgb(15, 23, 42);
        }

        .payroll-field {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .payroll-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: rgb(71, 85, 105);
        }

        .dark .payroll-label {
            color: rgb(203, 213, 225);
        }

        .payroll-input,
        .payroll-select {
            width: 100%;
            height: 2.25rem;
            padding: 0.3rem 0.65rem;
            border-radius: 0.45rem;
            border: 1px solid rgb(203, 213, 225);
            background: #ffffff;
            color: rgb(15, 23, 42);
            font-size: 0.82rem;
            font-weight: 500;
            outline: none;
        }

        .payroll-input:focus,
        .payroll-select:focus {
            border-color: rgb(37, 99, 235);
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
        }

        .dark .payroll-input,
        .dark .payroll-select {
            border-color: rgb(71, 85, 105);
            background: rgb(30, 41, 59);
            color: #ffffff;
        }

        .table-wrapper {
            overflow-x: auto;
            max-inline-size: 100%;
            border-radius: 0.65rem;
            border: 1px solid rgb(226, 232, 240);
            background: transparent;
        }

        .dark .table-wrapper {
            border-color: rgb(51, 65, 85);
            background: transparent;
        }

        .payroll-table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            font-size: 0.8rem;
        }

        .payroll-table thead {
            position: sticky;
            inset-block-start: 0;
            z-index: 1;
            background: rgb(248, 250, 252);
            border-bottom: 1px solid rgb(203, 213, 225);
        }

        .dark .payroll-table thead {
            background: rgb(30, 41, 59);
            border-bottom-color: rgb(51, 65, 85);
        }

        .payroll-table th,
        .payroll-table td {
            padding: 0.48rem 0.65rem;
            text-align: left;
            vertical-align: middle;
            border-bottom: 1px solid rgb(226, 232, 240);
            white-space: nowrap;
        }

        .dark .payroll-table th,
        .dark .payroll-table td {
            border-color: rgb(51, 65, 85);
        }

        .payroll-table thead th {
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: rgb(71, 85, 105);
            line-height: 1.2;
        }

        .dark .payroll-table thead th {
            color: rgb(203, 213, 225);
        }

        .payroll-table tbody tr {
            background: transparent;
            transition: background-color 100ms ease;
        }

        .payroll-table tbody tr:hover {
            background-color: rgba(59, 130, 246, 0.04);
        }

        .dark .payroll-table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.03);
        }

        .payroll-col-idx {
            width: 2.5rem;
            min-width: 2.5rem;
            text-align: center !important;
            font-weight: 700;
            font-size: 0.72rem;
            color: rgb(148, 163, 184);
        }

        .dark .payroll-col-idx {
            color: rgb(100, 116, 139);
        }

        .payroll-emp-name {
            font-weight: 700;
            color: rgb(15, 23, 42);
        }

        .dark .payroll-emp-name {
            color: #ffffff;
        }

        .payroll-cell-muted {
            color: rgb(100, 116, 139);
            font-size: 0.78rem;
        }

        .dark .payroll-cell-muted {
            color: rgb(148, 163, 184);
        }

        .payroll-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.12rem 0.45rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .payroll-pill.success {
            background: rgb(220, 252, 231);
            color: rgb(21, 128, 61);
        }

        .dark .payroll-pill.success {
            background: rgba(22, 163, 74, 0.2);
            color: rgb(74, 222, 128);
        }

        .payroll-pill.neutral {
            background: rgb(241, 245, 249);
            color: rgb(71, 85, 105);
            border: 1px solid rgb(226, 232, 240);
        }

        .dark .payroll-pill.neutral {
            background: rgb(30, 41, 59);
            color: rgb(203, 213, 225);
            border-color: rgb(51, 65, 85);
        }

        .payroll-row-actions {
            display: inline-flex;
            flex-direction: row;
            flex-wrap: nowrap;
            align-items: center;
            gap: 0.35rem;
            white-space: nowrap;
        }

        .payroll-table-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.26rem 0.55rem;
            border-radius: 0.375rem;
            font-size: 0.72rem;
            font-weight: 700;
            line-height: 1.2;
            text-decoration: none;
            white-space: nowrap;
            transition: all 120ms ease;
            cursor: pointer;
        }

        .payroll-table-btn.primary {
            background: rgb(37, 99, 235);
            color: #ffffff;
            border: 1px solid transparent;
        }

        .payroll-table-btn.primary:hover {
            background: rgb(29, 78, 216);
        }

        .payroll-table-btn.outline {
            background: #ffffff;
            color: rgb(30, 41, 59);
            border: 1px solid rgb(203, 213, 225);
        }

        .payroll-table-btn.outline:hover {
            background: rgb(248, 250, 252);
            border-color: rgb(148, 163, 184);
        }

        .dark .payroll-table-btn.outline {
            background: rgb(30, 41, 59);
            color: rgb(226, 232, 240);
            border-color: rgb(71, 85, 105);
        }

        .dark .payroll-table-btn.outline:hover {
            background: rgb(51, 65, 85);
        }

        @media (max-width: 1024px) {
            .payroll-filter-card {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 640px) {
            .payroll-topbar {
                align-items: stretch;
                padding: 1rem;
            }

            .payroll-title {
                font-size: 1.35rem;
            }

            .payroll-actions-row {
                justify-content: flex-start;
                width: 100%;
            }

            .payroll-filter-card {
                grid-template-columns: 1fr;
            }
        }

        /* Black content surfaces, with the gradient page header left intact. */
        .payroll-workspace .payroll-filter-card,
        .payroll-workspace .table-wrapper,
        .payroll-workspace .payroll-table,
        .payroll-workspace .payroll-table thead,
        .payroll-workspace .payroll-table tbody,
        .payroll-workspace .payroll-table tbody tr {
            background: #000000 !important;
        }

        .payroll-workspace .payroll-filter-card {
            border-color: #303030;
        }

        .payroll-workspace .table-wrapper {
            border-color: #303030;
        }

        .payroll-workspace .payroll-label,
        .payroll-workspace .payroll-emp-name,
        .payroll-workspace .payroll-table tbody td {
            color: #f8fafc;
        }

        .payroll-workspace .payroll-table thead th,
        .payroll-workspace .payroll-cell-muted,
        .payroll-workspace .payroll-col-idx {
            color: #94a3b8;
        }

        .payroll-workspace .payroll-table th,
        .payroll-workspace .payroll-table td {
            border-color: #27272a;
        }

        .payroll-workspace .payroll-table tbody tr:hover {
            background: #171717 !important;
        }

        .payroll-workspace .payroll-table tbody td[colspan] {
            color: #94a3b8 !important;
        }

        .payroll-workspace .payroll-input,
        .payroll-workspace .payroll-select {
            background: #000000 !important;
            border-color: #3f3f46;
            color: #f8fafc !important;
            color-scheme: dark;
        }

        .payroll-workspace .payroll-input::placeholder {
            color: #94a3b8;
        }

        .payroll-workspace .payroll-select option {
            background: #000000;
            color: #f8fafc;
        }

        .payroll-workspace .payroll-input:focus,
        .payroll-workspace .payroll-select:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.25);
        }

        .payroll-workspace .payroll-filter-card .payroll-btn.outline,
        .payroll-workspace .payroll-table-btn.outline {
            background: #000000;
            border-color: #3f3f46;
            color: #e2e8f0;
        }

        .payroll-workspace .payroll-filter-card .payroll-btn.outline:hover,
        .payroll-workspace .payroll-table-btn.outline:hover {
            background: #171717;
            border-color: #52525b;
            color: #ffffff;
        }
    </style>

    <div class="payroll-workspace">
        <header class="payroll-topbar">
            <div class="payroll-topbar-copy">
                <span class="payroll-eyebrow">WORKFORCE RECORDS</span>
                <h2 class="payroll-title">Payroll Processing</h2>
                <p class="payroll-subtitle">
                    Process payroll by selected period, branch, and employee.
                </p>
            </div>

            <div class="payroll-actions-row">
                @if ($this->payrollByBranchUrl())
                    <a href="{{ $this->payrollByBranchUrl() }}" class="payroll-btn outline">
                        <x-filament::icon icon="heroicon-o-building-storefront" class="payroll-btn-icon" />
                        Payroll Branch/Department
                    </a>
                @endif

                @if ($this->payrollSummaryUrl())
                    <a href="{{ $this->payrollSummaryUrl() }}" class="payroll-btn outline">
                        <x-filament::icon icon="heroicon-o-chart-bar" class="payroll-btn-icon" />
                        Payroll Summary
                    </a>
                @endif

                <x-filament::dropdown placement="bottom-end" teleport>
                    <x-slot name="trigger">
                        <button type="button" class="payroll-btn secondary">
                            <x-filament::icon icon="heroicon-o-cog-6-tooth" class="payroll-btn-icon" />
                            <span>Payroll Settings</span>
                            <x-filament::icon icon="heroicon-m-chevron-down" class="payroll-btn-icon" />
                        </button>
                    </x-slot>

                    <x-filament::dropdown.list>
                        @if ($this->payrollRosterUrl())
                            <x-filament::dropdown.list.item
                                :href="$this->payrollRosterUrl()"
                                tag="a"
                                icon="heroicon-o-clipboard-document-list"
                            >
                                Payroll Roster
                            </x-filament::dropdown.list.item>
                        @endif

                        <x-filament::dropdown.list.item
                            :href="$this->managePayrollPeriodsUrl()"
                            tag="a"
                            icon="heroicon-o-calendar-days"
                        >
                            Manage Payroll Periods
                        </x-filament::dropdown.list.item>

                        <x-filament::dropdown.list.item
                            :href="$this->payrollVisibilityUrl()"
                            tag="a"
                            icon="heroicon-o-eye"
                        >
                            Payroll Visibility
                        </x-filament::dropdown.list.item>
                    </x-filament::dropdown.list>
                </x-filament::dropdown>
            </div>
        </header>

        <section class="payroll-filter-card" aria-label="Payroll Filters">
            <div class="payroll-field">
                <label class="payroll-label" for="payroll-period-filter">Payroll Period</label>
                <select id="payroll-period-filter" wire:model.live="periodId" class="payroll-select">
                    <option value="">Select Payroll Period</option>
                    @foreach ($periods as $periodOption)
                        <option value="{{ $periodOption->id }}">
                            {{ $periodOption->title }} ({{ $periodOption->is_locked ? 'Locked' : 'Open' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="payroll-field">
                <label class="payroll-label" for="payroll-branch-filter">Branch / Station</label>
                <select id="payroll-branch-filter" wire:model.live="branchId" class="payroll-select">
                    <option value="">All Branches / Stations</option>
                    @foreach ($branches as $branchOption)
                        <option value="{{ $branchOption->id }}">{{ $branchOption->branch_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="payroll-field">
                <label class="payroll-label" for="payroll-employee-search">Search Employee</label>
                <input
                    id="payroll-employee-search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search name, company ID, or designation..."
                    class="payroll-input"
                />
            </div>

            <div>
                <button type="button" wire:click="resetFilters" class="payroll-btn outline" style="height: 2.35rem;">
                    Reset
                </button>
            </div>
        </section>

        <div class="table-wrapper">
            <table class="payroll-table">
                <thead>
                    <tr>
                        <th scope="col" class="payroll-col-idx">#</th>
                        <th scope="col">Employee</th>
                        <th scope="col">Company ID</th>
                        <th scope="col">Branch / Station</th>
                        <th scope="col">Designation</th>
                        <th scope="col">Rate Type</th>
                        <th scope="col">DTR Rows</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php
                            /** @var \App\Models\Employee $emp */
                            $emp = $row['employee'];
                        @endphp
                        <tr>
                            <td class="payroll-col-idx">{{ $loop->iteration }}</td>
                            <td>
                                <span class="payroll-emp-name">{{ $emp->full_name }}</span>
                            </td>
                            <td>
                                <span class="payroll-pill neutral">{{ $emp->company_id ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <span>{{ $emp->branch?->branch_name ?? 'Unassigned' }}</span>
                            </td>
                            <td>
                                <span class="payroll-cell-muted">{{ $emp->designation?->title ?? '—' }}</span>
                            </td>
                            <td>
                                <span class="payroll-cell-muted">{{ ucfirst((string) ($emp->rate_type ?: 'Monthly')) }}</span>
                            </td>
                            <td>
                                @if ($row['dtr_count'] > 0)
                                    <span class="payroll-pill success">{{ $row['dtr_count'] }} {{ Str::plural('entry', $row['dtr_count']) }}</span>
                                @else
                                    <span class="payroll-pill neutral">0 entries</span>
                                @endif
                            </td>
                            <td>
                                <div class="payroll-row-actions">
                                    @if ($selectedPeriod)
                                        <a href="{{ $row['view_payroll_url'] }}" class="payroll-table-btn primary">
                                            View Payroll
                                        </a>
                                        @if ($row['payslip_url'])
                                            <a href="{{ $row['payslip_url'] }}" target="_blank" class="payroll-table-btn outline">
                                                Payslip
                                            </a>
                                        @endif
                                    @else
                                        <span class="payroll-cell-muted">Select period</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 1.5rem; font-weight: 500; color: rgb(100, 116, 139);">
                                No matching employees found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
