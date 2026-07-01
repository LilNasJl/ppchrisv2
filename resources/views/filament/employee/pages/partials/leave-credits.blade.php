<div style="display: grid; gap: 12px;">
    <div>
        <div style="font-size: 12px; color: #94a3b8;">Leave Count</div>
        <div style="font-size: 22px; font-weight: 800;">{{ number_format((float) $employee->leave_credits, 2) }}</div>
    </div>
    <div>
        <div style="font-size: 12px; color: #94a3b8;">Birthday Leave</div>
        <div style="font-size: 22px; font-weight: 800;">{{ number_format((float) $employee->birthday_leave_credits, 2) }}</div>
    </div>
</div>
