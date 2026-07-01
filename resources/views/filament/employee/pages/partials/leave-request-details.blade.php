@if ($leave)
    <div style="display: grid; gap: 12px; font-size: 14px;">
        <div>
            <div style="color: #94a3b8; font-size: 12px;">Date Filed</div>
            <div style="font-weight: 700;">{{ $leave->created_at?->format('M d, Y h:i A') }}</div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px;">
            <div>
                <div style="color: #94a3b8; font-size: 12px;">Leave Type</div>
                <div style="font-weight: 700;">{{ $leave->leave_type }}</div>
            </div>
            <div>
                <div style="color: #94a3b8; font-size: 12px;">Status</div>
                <div style="font-weight: 700;">{{ $leave->status }}</div>
            </div>
            <div>
                <div style="color: #94a3b8; font-size: 12px;">From</div>
                <div style="font-weight: 700;">{{ $leave->leave_from?->format('M d, Y') }}</div>
            </div>
            <div>
                <div style="color: #94a3b8; font-size: 12px;">To</div>
                <div style="font-weight: 700;">{{ $leave->leave_to?->format('M d, Y') }}</div>
            </div>
            <div>
                <div style="color: #94a3b8; font-size: 12px;">Half Day</div>
                <div style="font-weight: 700;">{{ $leave->is_half_day ? 'Yes' : 'No' }}</div>
            </div>
            <div>
                <div style="color: #94a3b8; font-size: 12px;">Requested Days</div>
                <div style="font-weight: 700;">{{ $leave->getRequestedLeaveDays() }}</div>
            </div>
            <div>
                <div style="color: #94a3b8; font-size: 12px;">Approved/Rejected By</div>
                <div style="font-weight: 700;">{{ $leave->reviewedBy?->name ?: '-' }}</div>
            </div>
            <div>
                <div style="color: #94a3b8; font-size: 12px;">Approved/Rejected At</div>
                <div style="font-weight: 700;">{{ $leave->reviewed_at?->format('M d, Y h:i A') ?: '-' }}</div>
            </div>
        </div>

        <div>
            <div style="color: #94a3b8; font-size: 12px;">Reason</div>
            <div style="white-space: pre-line;">{{ $leave->reason }}</div>
        </div>

        @if ($leave->hr_comment)
            <div>
                <div style="color: #94a3b8; font-size: 12px;">HR Comment</div>
                <div style="white-space: pre-line;">{{ $leave->hr_comment }}</div>
            </div>
        @endif

        @if ($leave->attachment_url)
            <div>
                <div style="color: #94a3b8; font-size: 12px;">Attached File</div>
                <a href="{{ $leave->attachment_url }}" target="_blank" style="font-weight: 700; color: #2563eb;">
                    {{ $leave->attachment_name }}
                </a>
            </div>
        @endif
    </div>
@else
    <div style="color: #94a3b8;">Leave request not found.</div>
@endif
