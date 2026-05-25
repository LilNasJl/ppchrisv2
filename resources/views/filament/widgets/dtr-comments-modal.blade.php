<div style="overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th style="padding: 10px; text-align: left; border-bottom: 1px solid rgba(148, 163, 184, .35);">#</th>
                <th style="padding: 10px; text-align: left; border-bottom: 1px solid rgba(148, 163, 184, .35);">Date In</th>
                <th style="padding: 10px; text-align: left; border-bottom: 1px solid rgba(148, 163, 184, .35);">Date Out</th>
                <th style="padding: 10px; text-align: left; border-bottom: 1px solid rgba(148, 163, 184, .35);">Comment</th>
                <th style="padding: 10px; text-align: right; border-bottom: 1px solid rgba(148, 163, 184, .35);">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($comments as $comment)
                @php
                    $dateIn = filled($comment->date_in) ? \Carbon\Carbon::parse($comment->date_in)->format('M d, Y') : '-';
                    $dateOut = filled($comment->date_out) ? \Carbon\Carbon::parse($comment->date_out)->format('M d, Y') : '-';
                    $isLocked = (bool) $comment->is_locked || (bool) $comment->payrollPeriod?->is_locked;
                @endphp
                <tr>
                    <td style="padding: 10px; vertical-align: top; border-bottom: 1px solid rgba(148, 163, 184, .18);">{{ $loop->iteration }}</td>
                    <td style="padding: 10px; vertical-align: top; border-bottom: 1px solid rgba(148, 163, 184, .18); white-space: nowrap;">{{ $dateIn }}</td>
                    <td style="padding: 10px; vertical-align: top; border-bottom: 1px solid rgba(148, 163, 184, .18); white-space: nowrap;">{{ $dateOut }}</td>
                    <td style="padding: 10px; vertical-align: top; border-bottom: 1px solid rgba(148, 163, 184, .18); min-width: 260px;">{{ $comment->comment }}</td>
                    <td style="padding: 10px; vertical-align: top; text-align: right; border-bottom: 1px solid rgba(148, 163, 184, .18);">
                        <button
                            type="button"
                            wire:click="deleteDtrComment({{ $comment->id }})"
                            @disabled($isLocked)
                            style="border: 0; border-radius: 6px; cursor: {{ $isLocked ? 'not-allowed' : 'pointer' }}; opacity: {{ $isLocked ? '.55' : '1' }}; padding: 7px 10px; background: #dc2626; color: #fff; font-weight: 700;"
                        >
                            {{ $isLocked ? 'Locked' : 'Delete' }}
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="padding: 18px; text-align: center; color: #64748b;">
                        No D.T.R comments for this employee and payroll period.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
