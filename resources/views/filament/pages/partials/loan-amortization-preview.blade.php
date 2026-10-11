<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;padding:12px;border:1px solid #334155;border-radius:6px;background:#10141c;color:#e2e8f0;">
    @foreach (['interest' => 'Total Interest', 'total' => 'Total Repayable', 'payment' => 'Regular Deduction', 'final_payment' => 'Final Deduction'] as $key => $label)
        <div><div style="font-size:12px;color:#93c5fd;">{{ $label }}</div><strong>{{ number_format($preview[$key], 2) }}</strong></div>
    @endforeach
    <div><div style="font-size:12px;color:#93c5fd;">Scheduled Deductions</div><strong>{{ $preview['deductions'] }}</strong></div>
    <div style="grid-column:1/-1;font-size:12px;overflow-wrap:anywhere;">First Deduction: <strong>{{ $preview['first_period'] }}</strong></div>
</div>
