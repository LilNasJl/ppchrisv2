<tr x-show="expanded['{{ $row['group_key'] }}']" x-cloak class="summary-employees-subrow">
    <td colspan="8" class="summary-employees-container-cell">
        <div class="summary-employee-dropdown-panel">
            <div class="summary-employee-panel-header">
                <div class="summary-employee-panel-title">
                    <x-filament::icon icon="heroicon-m-users" class="summary-emp-panel-icon" />
                    <span>Employees in <strong>{{ $row['branch'] }}</strong></span>
                </div>
                <span class="summary-emp-count-badge">
                    {{ count($row['employees'] ?? []) }} {{ count($row['employees'] ?? []) === 1 ? 'Worker' : 'Workers' }}
                </span>
            </div>

            <div class="summary-employee-table-wrapper">
                <table class="summary-employee-subtable">
                    <thead>
                        <tr>
                            <th class="sub-col-idx">#</th>
                            <th class="sub-col-name text-left">Employee Name</th>
                            <th class="sub-col-gross text-right">Gross Pay</th>
                            <th class="sub-col-net text-right">Net Pay</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($row['employees'] ?? [] as $eIdx => $emp)
                            <tr>
                                <td class="sub-col-idx text-center">{{ $eIdx + 1 }}</td>
                                <td class="sub-col-name text-left font-medium">{{ $emp['name'] }}</td>
                                <td class="sub-col-gross text-right">{{ $money($emp['gross_pay']) }}</td>
                                <td class="sub-col-net text-right font-bold">{{ $money($emp['net_pay']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="sub-col-empty">No employees found in this group.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </td>
</tr>
