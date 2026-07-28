<x-filament-panels::page>
    <style>
        .kpi-directory {
            display: grid;
            gap: 18px;
        }

        .kpi-directory-panel {
            background: #fff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            box-shadow: 0 10px 28px rgba(15, 71, 148, .08);
        }

        .kpi-directory-toolbar {
            align-items: end;
            display: flex;
            gap: 18px;
            justify-content: space-between;
            padding: 20px;
        }

        .kpi-directory-title {
            color: #0f172a;
            font-size: 16px;
            font-weight: 800;
            margin: 0;
        }

        .kpi-directory-description {
            color: #64748b;
            font-size: 13px;
            line-height: 1.55;
            margin: 5px 0 0;
        }

        .kpi-directory-search {
            align-items: center;
            background: #fff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            display: flex;
            flex: 0 1 360px;
            gap: 8px;
            min-height: 42px;
            padding: 0 12px;
        }

        .kpi-directory-search:focus-within {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .14);
        }

        .kpi-directory-search svg {
            color: #2563eb;
            height: 20px;
            width: 20px;
        }

        .kpi-directory-search input {
            background: transparent;
            border: 0;
            color: #0f172a;
            flex: 1;
            font-size: 13px;
            min-width: 0;
            outline: 0;
        }

        .kpi-directory-tabs {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            padding: 0 20px 20px;
        }

        .kpi-directory-tab {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            color: #1e40af;
            cursor: pointer;
            font-size: 13px;
            font-weight: 800;
            min-height: 44px;
            padding: 9px 14px;
        }

        .kpi-directory-tab:hover {
            background: #dbeafe;
            border-color: #60a5fa;
        }

        .kpi-directory-tab.is-active {
            background: #1d4ed8;
            border-color: #1d4ed8;
            color: #fff;
        }

        .kpi-directory-table-wrap {
            overflow-x: auto;
        }

        .kpi-directory-table {
            border-collapse: collapse;
            min-width: 760px;
            width: 100%;
        }

        .kpi-directory-table th {
            background: #eff6ff;
            color: #172554;
            font-size: 11px;
            font-weight: 800;
            padding: 12px 16px;
            text-align: left;
            text-transform: uppercase;
        }

        .kpi-directory-table td {
            border-top: 1px solid #dbeafe;
            color: #334155;
            font-size: 13px;
            padding: 12px 16px;
            vertical-align: middle;
        }

        .kpi-directory-table tbody tr:nth-child(even) {
            background: rgba(239, 246, 255, .55);
        }

        .kpi-directory-table tbody tr:hover {
            background: #eff6ff;
        }

        .kpi-directory-name {
            color: #0f172a;
            font-weight: 700;
        }

        .kpi-directory-meta {
            color: #64748b;
            font-size: 11px;
            margin-top: 3px;
        }

        .kpi-directory-action {
            text-align: right !important;
        }

        .kpi-directory-empty {
            color: #64748b !important;
            padding: 46px 24px !important;
            text-align: center;
        }

        .kpi-modal-message {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 18px;
        }

        .kpi-modal-message strong {
            color: #172554;
            display: block;
        }

        .kpi-modal-message p {
            color: #1e40af;
            font-size: 13px;
            line-height: 1.55;
            margin: 7px 0 0;
        }

        .dark .kpi-directory-panel,
        .dark .kpi-directory-search {
            background: #111827;
            border-color: rgba(30, 64, 175, .72);
        }

        .dark .kpi-directory-title,
        .dark .kpi-directory-name {
            color: #f8fafc;
        }

        .dark .kpi-directory-description,
        .dark .kpi-directory-table td,
        .dark .kpi-directory-search input {
            color: #cbd5e1;
        }

        .dark .kpi-directory-tab,
        .dark .kpi-directory-table th,
        .dark .kpi-modal-message {
            background: rgba(30, 64, 175, .2);
            border-color: rgba(30, 64, 175, .72);
            color: #dbeafe;
        }

        .dark .kpi-directory-tab.is-active {
            background: #2563eb;
            color: #fff;
        }

        .dark .kpi-directory-table td {
            border-color: rgba(30, 64, 175, .45);
        }

        .dark .kpi-directory-table tbody tr:nth-child(even),
        .dark .kpi-directory-table tbody tr:hover {
            background: rgba(30, 64, 175, .08);
        }

        .dark .kpi-modal-message strong,
        .dark .kpi-modal-message p {
            color: #dbeafe;
        }

        @media (max-width: 900px) {
            .kpi-directory-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .kpi-directory-search {
                flex-basis: auto;
                max-width: none;
                width: 100%;
            }

            .kpi-directory-tabs {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 520px) {
            .kpi-directory-tabs {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="kpi-directory">
        <section class="kpi-directory-panel">
            <div class="kpi-directory-toolbar">
                <div>
                    <h2 class="kpi-directory-title">KPI Rating Directory</h2>
                    <p class="kpi-directory-description">
                        Choose the workforce view needed for KPI preparation. Rating criteria will be configured separately.
                    </p>
                </div>

                <label class="kpi-directory-search">
                    <x-filament::icon icon="heroicon-m-magnifying-glass" />
                    <input
                        type="search"
                        wire:model.live.debounce.350ms="search"
                        placeholder="Search the current view"
                        aria-label="Search KPI directory"
                    >
                </label>
            </div>

            <div class="kpi-directory-tabs" role="tablist" aria-label="KPI directory views">
                @foreach ($this->viewOptions() as $key => $label)
                    <button
                        type="button"
                        wire:click="setViewMode('{{ $key }}')"
                        @class(['kpi-directory-tab', 'is-active' => $viewMode === $key])
                        role="tab"
                        aria-selected="{{ $viewMode === $key ? 'true' : 'false' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </section>

        <section class="kpi-directory-panel kpi-directory-table-wrap">
            <table class="kpi-directory-table">
                <thead>
                    <tr>
                        <th style="width: 56px;">#</th>

                        @if ($viewMode === 'departments')
                            <th>Department</th>
                            <th style="width: 176px;">No. of Employees</th>
                        @elseif ($viewMode === 'branches')
                            <th>Branch</th>
                            <th style="width: 176px;">No. of Employees</th>
                        @else
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Branch</th>
                        @endif

                        <th class="kpi-directory-action" style="width: 164px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->rows() as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>

                            @if ($viewMode === 'departments')
                                <td>
                                    <span class="kpi-directory-name">{{ $row->name }}</span>
                                    @if (filled($row->acronym))
                                        <span class="kpi-directory-meta">({{ $row->acronym }})</span>
                                    @endif
                                </td>
                                <td>{{ number_format($row->employees_count) }}</td>
                            @elseif ($viewMode === 'branches')
                                <td><span class="kpi-directory-name">{{ $row->branch_name }}</span></td>
                                <td>{{ number_format($row->employees_count) }}</td>
                            @else
                                <td>
                                    <div class="kpi-directory-name">{{ $row->full_name }}</div>
                                    <div class="kpi-directory-meta">{{ $row->company_id ?: 'No employee ID' }}</div>
                                </td>
                                <td>{{ $row->department?->name ?: '-' }}</td>
                                <td>{{ $row->designation?->title ?: '-' }}</td>
                                <td>{{ $row->branch?->branch_name ?: '-' }}</td>
                            @endif

                            <td class="kpi-directory-action">
                                @if ($viewMode === 'branches')
                                    <x-filament::button
                                        tag="a"
                                        :href="\App\Filament\Pages\KpiBranchEmployees::getUrl(['branch' => $row->id])"
                                        size="sm"
                                        icon="heroicon-m-eye"
                                    >
                                        View
                                    </x-filament::button>
                                @else
                                    <x-filament::modal id="kpi-rate-{{ $viewMode }}-{{ $row->id }}" width="lg">
                                        <x-slot name="trigger">
                                            <x-filament::button size="sm" icon="heroicon-m-chart-bar-square">
                                                KPI Rate
                                            </x-filament::button>
                                        </x-slot>

                                        <x-slot name="heading">KPI Rating</x-slot>

                                        @include('filament.pages.partials.kpi-rating-placeholder', [
                                            'title' => $viewMode === 'departments' ? $row->name : $row->full_name,
                                        ])
                                    </x-filament::modal>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="kpi-directory-empty">No matching KPI records were found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
</x-filament-panels::page>
