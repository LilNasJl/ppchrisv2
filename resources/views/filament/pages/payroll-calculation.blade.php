<x-filament-panels::page>
    <header style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem; padding:1.25rem 1.5rem; border:1px solid #3b82f6; border-radius:8px; background:linear-gradient(110deg,#1257b8,#173c70 68%,#102a50); color:white;">
        <div>
            <p style="margin:0; color:#d9f722; font-size:12px; font-weight:800;">PAYROLL RULES</p>
            <h2 style="margin:4px 0; font-size:26px; font-weight:800;">Payroll Settings</h2>
            <p style="margin:0; font-size:14px;">{{ $this->period_display }} &middot; {{ $this->period?->is_locked ? 'Locked - read only' : 'Changes apply to this open period only' }}</p>
        </div>
        <x-filament::button tag="a" :href="\App\Filament\Pages\Payroll::getUrl(['periodId' => $this->period?->publicKey()])" icon="heroicon-o-arrow-left">Return</x-filament::button>
    </header>
    <style>
        .payroll-formula-grid {
            display: grid;
            gap: 16px;
            width: min(100%, 1100px);
        }

        .payroll-formula-table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
            border-radius: 8px;
            border: 1px solid rgba(148, 163, 184, .28);
        }

        .payroll-formula-table th,
        .payroll-formula-table td {
            padding: 10px 12px;
            border-bottom: 1px solid rgba(148, 163, 184, .22);
            text-align: left;
            vertical-align: top;
        }

        .payroll-formula-table th {
            font-size: 12px;
            text-transform: uppercase;
            color: #475569;
        }

        .dark .payroll-formula-table th {
            color: #cbd5e1;
        }

        .payroll-formula-table td:first-child {
            font-weight: 700;
            white-space: nowrap;
        }

        @media (max-width: 720px) {
            .payroll-formula-table,
            .payroll-formula-table tbody,
            .payroll-formula-table tr,
            .payroll-formula-table td {
                display: block;
                width: 100%;
            }

            .payroll-formula-table thead {
                display: none;
            }

            .payroll-formula-table td:first-child {
                white-space: normal;
                padding-bottom: 2px;
            }
        }
    </style>

    <div class="payroll-formula-grid">
        <form wire:submit.prevent="mountAction('saveSettings')" style="display: grid; gap: 16px;">
            {{ $this->form }}

            <div>
                {{ $this->saveSettingsAction }}
            </div>
        </form>

        <x-filament::section>
            <x-slot name="heading">
                Formula Reference
            </x-slot>

            <table class="payroll-formula-table">
                <thead>
                    <tr>
                        <th>Calculation</th>
                        <th>Formula Used</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->formulaRows as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['formula'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-filament::section>
    </div>
</x-filament-panels::page>
