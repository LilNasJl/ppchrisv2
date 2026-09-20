@include('filament.leave.styles')
@if ($leave)
    @php
        $leave->loadMissing(['employee.branch', 'employee.department', 'employee.designation', 'approvalSteps', 'approvalEvents', 'reviewedBy']);
        $snapshot = $leave->approval_snapshot ?? [];
        $colors = ['Pending' => 'warning', 'Waiting' => 'gray', 'Approved' => 'success', 'Rejected' => 'danger', 'Cancelled' => 'gray', 'Skipped' => 'warning'];
    @endphp
    <div class="leave-review">
        <header class="overview">
            <div>
                <h2>{{ $snapshot['employee_name'] ?? $leave->employee?->full_name ?? 'Employee unavailable' }}</h2>
                <p class="muted">{{ $snapshot['employee_company_id'] ?? $leave->employee?->company_id }} / {{ $snapshot['branch'] ?? $leave->employee?->branch?->branch_name }}</p>
            </div>
            <x-filament::badge :color="$colors[$leave->status] ?? 'gray'">{{ $leave->approval_label }}</x-filament::badge>
        </header>
        <dl class="details-grid">
            <div><dt>Leave Type</dt><dd>{{ $leave->leave_type }}</dd></div>
            <div><dt>Dates</dt><dd>{{ $leave->leave_from?->format('M d, Y') }} - {{ $leave->leave_to?->format('M d, Y') }}</dd></div>
            <div><dt>Requested Days</dt><dd>{{ $leave->getRequestedLeaveDays() }}{{ $leave->is_half_day ? ' / '.ucfirst($leave->half_day_period) : '' }}</dd></div>
            <div><dt>Department / Designation</dt><dd>{{ $snapshot['department'] ?? $leave->employee?->department?->name ?? '-' }} / {{ $snapshot['designation'] ?? $leave->employee?->designation?->title ?? '-' }}</dd></div>
            <div><dt>Submitted</dt><dd>{{ $leave->created_at?->format('M d, Y h:i A') }}</dd></div>
            <div><dt>Workflow</dt><dd>{{ $snapshot['workflow'] ?? 'Legacy HR review' }}{{ isset($snapshot['version']) ? ' / Revision '.$snapshot['version'] : '' }}</dd></div>
        </dl>
        <div class="columns">
            <div>
                <section class="band">
                    <h3>Reason</h3>
                    <p class="prose">{{ $leave->reason }}</p>
                </section>
                <section class="band">
                    <h3>Supporting Attachment</h3>
                    @if ($leave->attachment_url)
                        <div class="attachment">
                            <span>{{ $leave->attachment_name }}</span>
                            <x-filament::button tag="a" :href="$leave->attachment_url" target="_blank" rel="noopener" icon="heroicon-o-arrow-top-right-on-square" color="gray" outlined>View Attachment</x-filament::button>
                        </div>
                    @else
                        <p class="prose muted">No attachment submitted.</p>
                    @endif
                </section>
                @if ($leave->hr_comment)
                    <section class="band"><h3>HR Remarks</h3><p class="prose">{{ $leave->hr_comment }}</p></section>
                @endif
            </div>
            <section class="band">
                <h3>Approval Progress</h3>
                <ol class="timeline">
                    @forelse ($leave->approvalSteps as $step)
                        <li @class(['current' => $step->status === 'Pending'])>
                            <div class="step-line"><span class="step-name">{{ $step->sequence }}. {{ $step->label }}</span><x-filament::badge :color="$colors[$step->status] ?? 'gray'">{{ $step->status }}</x-filament::badge></div>
                            <p class="muted">{{ $step->approver_name ?: 'Authorized HR reviewer' }}</p>
                            @if ($step->acted_at)<p class="muted">{{ $step->acted_at->format('M d, Y h:i A') }}</p>@endif
                            @if ($step->remarks)<p class="prose">{{ $step->remarks }}</p>@endif
                        </li>
                    @empty
                        <li><div class="step-line"><span class="step-name">HR Review</span><x-filament::badge :color="$colors[$leave->status] ?? 'gray'">{{ $leave->status }}</x-filament::badge></div>
                            <p class="muted">{{ $leave->reviewedBy?->name }} {{ $leave->reviewed_at?->format('M d, Y h:i A') }}</p>
                        </li>
                    @endforelse
                </ol>
            </section>
        </div>
        @if ($leave->approvalEvents->isNotEmpty())
            <section class="band">
                <h3>Activity History</h3>
                <div class="scroll"><table class="audit">
                    <thead><tr><th>Action</th><th>By</th><th>Date / Time</th><th>Remarks</th></tr></thead>
                    <tbody>@foreach ($leave->approvalEvents as $event)
                        <tr><td>{{ $event->action }}</td><td>{{ $event->actor_name }}</td><td>{{ $event->created_at?->format('M d, Y h:i A') }}</td><td>{{ $event->remarks ?: '-' }}</td></tr>
                    @endforeach</tbody>
                </table></div>
            </section>
        @endif
    </div>
@endif
