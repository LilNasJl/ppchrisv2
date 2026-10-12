@php
    $employee = auth()->user()?->employee;
    $empDeductions = $employee ? $employee->employeeDeductions()->with('deduction')->get()->keyBy('deduction_id') : collect();
    $allDeductions = \App\Models\Deduction::all();

    $statutoryTitles = ['SSS EE', 'HDMF EE', 'PHIC EE'];
    $loanTitles = ['SSS LOAN', 'HDMF LOAN'];
    $companyTitles = ['SHORTAGES', 'COMPANY UNIFORM'];

    $items = $allDeductions->map(function ($d) use ($empDeductions, $statutoryTitles, $loanTitles, $companyTitles) {
        $amount = (float) ($empDeductions->get($d->id)?->amount ?? 0);
        $category = 'Other Deduction';
        $badgeClass = 'purple';
        if (in_array($d->title, $statutoryTitles)) {
            $category = 'Statutory Contribution';
            $badgeClass = 'blue';
        } elseif (in_array($d->title, $loanTitles)) {
            $category = 'Government Loan';
            $badgeClass = 'amber';
        } elseif (in_array($d->title, $companyTitles)) {
            $category = 'Company Deduction';
            $badgeClass = 'slate';
        }
        return (object) [
            'id' => $d->id,
            'title' => $d->title,
            'description' => $d->description,
            'category' => $category,
            'badgeClass' => $badgeClass,
            'amount' => $amount,
            'is_active' => $amount > 0,
        ];
    })->sortBy([
        fn ($a, $b) => $b->amount <=> $a->amount,
        fn ($a, $b) => $a->category <=> $b->category,
    ]);

    $totalDeductions = $items->sum('amount');
    $statutoryTotal = $items->where('category', 'Statutory Contribution')->sum('amount');
    $loansTotal = $items->where('category', 'Government Loan')->sum('amount');
    $otherTotal = $items->whereIn('category', ['Company Deduction', 'Other Deduction'])->sum('amount');
    $activeCount = $items->where('is_active', true)->count();
@endphp

<div class="emp-deductions-wrapper">
    {{-- KPI summary row --}}
    <div class="emp-deductions-kpis">
        <div class="emp-deduction-kpi primary">
            <span class="emp-kpi-label">TOTAL SCHEDULED DEDUCTIONS</span>
            <div class="emp-kpi-value text-blue-700 dark:text-blue-300">
                ₱{{ number_format($totalDeductions, 2) }}
            </div>
            <span class="emp-kpi-sub">{{ $activeCount }} active {{ \Illuminate\Support\Str::plural('item', $activeCount) }}</span>
        </div>

        <div class="emp-deduction-kpi">
            <span class="emp-kpi-label">STATUTORY CONTRIBUTIONS</span>
            <div class="emp-kpi-value text-slate-800 dark:text-slate-100">
                ₱{{ number_format($statutoryTotal, 2) }}
            </div>
            <span class="emp-kpi-sub">SSS, PhilHealth, Pag-IBIG EE</span>
        </div>

        <div class="emp-deduction-kpi">
            <span class="emp-kpi-label">GOVERNMENT LOANS</span>
            <div class="emp-kpi-value {{ $loansTotal > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-slate-500' }}">
                ₱{{ number_format($loansTotal, 2) }}
            </div>
            <span class="emp-kpi-sub">SSS & Pag-IBIG amortizations</span>
        </div>

        <div class="emp-deduction-kpi">
            <span class="emp-kpi-label">COMPANY & OTHER</span>
            <div class="emp-kpi-value {{ $otherTotal > 0 ? 'text-indigo-700 dark:text-indigo-400' : 'text-slate-500' }}">
                ₱{{ number_format($otherTotal, 2) }}
            </div>
            <span class="emp-kpi-sub">Uniform, HMO, shortages</span>
        </div>
    </div>

    {{-- Compact Deductions Table --}}
    <div class="emp-table-card">
        <div class="emp-table-header">
            <div>
                <h4 class="emp-table-title">Deduction Items & Schedules</h4>
                <p class="emp-table-subtitle">Summary of regular employee payroll deductions and statutory remittances</p>
            </div>
            <div class="emp-table-badge">
                <span class="emp-chip primary">
                    <x-filament::icon icon="heroicon-m-check-circle" class="w-3.5 h-3.5" />
                    {{ $activeCount }} Active Deductions
                </span>
            </div>
        </div>

        <div class="emp-table-scroll">
            <table class="emp-compact-table">
                <thead>
                    <tr>
                        <th style="width: 32%;">Deduction Item</th>
                        <th style="width: 25%;">Category</th>
                        <th style="width: 23%; text-align: right;">Scheduled Amount</th>
                        <th style="width: 20%; text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="{{ $item->is_active ? 'is-active-row' : 'is-muted-row' }}">
                            <td>
                                <div class="emp-item-cell">
                                    <span class="emp-item-name">{{ $item->title }}</span>
                                    @if ($item->description && $item->description !== $item->title)
                                        <span class="emp-item-desc">{{ $item->description }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="emp-cat-badge {{ $item->badgeClass }}">
                                    {{ $item->category }}
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <span class="emp-amount {{ $item->is_active ? 'active-amount' : 'zero-amount' }}">
                                    ₱{{ number_format($item->amount, 2) }}
                                </span>
                            </td>
                            <td style="text-align: center;">
                                @if ($item->is_active)
                                    <span class="emp-status-chip active">
                                        <span class="emp-dot-green"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="emp-status-chip muted">
                                        <span class="emp-dot-gray"></span>
                                        ₱0.00
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="emp-empty-td">
                                No deductions recorded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="emp-tfoot-label">Total Monthly / Periodic Deductions</td>
                        <td style="text-align: right;" class="emp-tfoot-total">₱{{ number_format($totalDeductions, 2) }}</td>
                        <td style="text-align: center;" class="emp-tfoot-sub">{{ $activeCount }} active</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
