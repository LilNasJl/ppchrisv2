<x-filament-panels::page>
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
        <form wire:submit.prevent="save" style="display: grid; gap: 16px;">
            {{ $this->form }}

            <div>
                <x-filament::button type="submit" icon="heroicon-m-check" :disabled="(bool) ($this->period?->is_locked)">
                    Save Payroll Calculation
                </x-filament::button>
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
