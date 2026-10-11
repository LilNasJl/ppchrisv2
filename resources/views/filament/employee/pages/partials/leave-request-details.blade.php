@include('filament.leave.styles')
@if ($leave)
    @php
        $leave->loadMissing(['employee.branch', 'employee.department', 'employee.designation', 'approvalSteps', 'approvalEvents', 'reviewedBy']);
        $snapshot = $leave->approval_snapshot ?? [];
        $colors = [
            'Pending' => 'warning',
            'Waiting' => 'gray',
            'Approved' => 'success',
            'Rejected' => 'danger',
            'Cancelled' => 'gray',
            'Skipped' => 'warning',
        ];

        $employeeName = $snapshot['employee_name'] ?? $leave->employee?->full_name ?? 'Employee unavailable';
        $companyId = $snapshot['employee_company_id'] ?? $leave->employee?->company_id ?? 'N/A';
        $branchName = $snapshot['branch'] ?? $leave->employee?->branch?->branch_name ?? 'Unassigned';
        $departmentName = $snapshot['department'] ?? $leave->employee?->department?->name ?? '-';
        $designationTitle = $snapshot['designation'] ?? $leave->employee?->designation?->title ?? '-';
        $requestedDays = $leave->getRequestedLeaveDays();

        $nameParts = preg_split('/[\s,]+/', trim((string) $employeeName), -1, PREG_SPLIT_NO_EMPTY);
        $initials = strtoupper(substr($nameParts[0] ?? 'E', 0, 1) . substr($nameParts[1] ?? ($nameParts[0] ?? 'M'), 0, 1));
    @endphp

    <div class="leave-review">
        {{-- 1. Employee & Status Overview Banner --}}
        <header class="overview">
            <div class="overview-identity">
                <div class="overview-avatar" aria-hidden="true">{{ $initials }}</div>
                <div class="overview-info">
                    <h2>{{ $employeeName }}</h2>
                    <div class="overview-chips">
                        <span class="overview-chip code">
                            <x-filament::icon icon="heroicon-m-identification" />
                            {{ $companyId }}
                        </span>
                        <span class="overview-chip">
                            <x-filament::icon icon="heroicon-m-building-storefront" />
                            {{ $branchName }}
                        </span>
                        @if ($departmentName && $departmentName !== '-')
                            <span class="overview-chip">
                                <x-filament::icon icon="heroicon-m-building-office" />
                                {{ $departmentName }}
                            </span>
                        @endif
                        @if ($designationTitle && $designationTitle !== '-')
                            <span class="overview-chip">
                                <x-filament::icon icon="heroicon-m-briefcase" />
                                {{ $designationTitle }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="overview-status-box">
                <span class="overview-status-label">Current Status</span>
                <x-filament::badge :color="$colors[$leave->status] ?? 'gray'">
                    {{ $leave->approval_label }}
                </x-filament::badge>
            </div>
        </header>

        {{-- 2. Key Leave Summary Cards --}}
        <dl class="details-grid">
            <div class="detail-card accent-blue">
                <div class="detail-card-head">
                    <dt>Leave Type</dt>
                    <span class="detail-icon">
                        <x-filament::icon icon="heroicon-o-calendar" />
                    </span>
                </div>
                <dd>{{ $leave->leave_type }}</dd>
                <span class="detail-sub">
                    {{ $leave->is_half_day ? 'Half-Day (' . ucfirst((string) $leave->half_day_period) . ')' : 'Full-Day Leave Schedule' }}
                </span>
            </div>

            <div class="detail-card accent-indigo">
                <div class="detail-card-head">
                    <dt>Dates</dt>
                    <span class="detail-icon">
                        <x-filament::icon icon="heroicon-o-calendar-days" />
                    </span>
                </div>
                <dd>{{ $leave->leave_from?->format('M d, Y') }} - {{ $leave->leave_to?->format('M d, Y') }}</dd>
                <span class="detail-sub">
                    @if ($leave->leave_from && $leave->leave_to)
                        {{ $leave->leave_from->isSameDay($leave->leave_to) ? $leave->leave_from->format('l') : ($leave->leave_from->format('D') . ' – ' . $leave->leave_to->format('D')) }}
                    @else
                        Inclusive leave period
                    @endif
                </span>
            </div>

            <div class="detail-card accent-emerald">
                <div class="detail-card-head">
                    <dt>Requested Days</dt>
                    <span class="detail-icon">
                        <x-filament::icon icon="heroicon-o-clock" />
                    </span>
                </div>
                <dd>{{ $requestedDays }}{{ $leave->is_half_day ? ' / ' . ucfirst((string) $leave->half_day_period) : ' ' . \Illuminate\Support\Str::plural('Day', $requestedDays) }}</dd>
                <span class="detail-sub">Total requested duration</span>
            </div>

            <div class="detail-card accent-purple">
                <div class="detail-card-head">
                    <dt>Department / Designation</dt>
                    <span class="detail-icon">
                        <x-filament::icon icon="heroicon-o-briefcase" />
                    </span>
                </div>
                <dd>{{ $departmentName }} / {{ $designationTitle }}</dd>
                <span class="detail-sub">Organizational assignment</span>
            </div>

            <div class="detail-card accent-sky">
                <div class="detail-card-head">
                    <dt>Submitted</dt>
                    <span class="detail-icon">
                        <x-filament::icon icon="heroicon-o-paper-airplane" />
                    </span>
                </div>
                <dd>{{ $leave->created_at?->format('M d, Y h:i A') }}</dd>
                <span class="detail-sub">{{ $leave->created_at?->diffForHumans() ?? 'Filing timestamp' }}</span>
            </div>

            <div class="detail-card accent-amber">
                <div class="detail-card-head">
                    <dt>Workflow</dt>
                    <span class="detail-icon">
                        <x-filament::icon icon="heroicon-o-arrows-right-left" />
                    </span>
                </div>
                <dd>{{ $snapshot['workflow'] ?? 'Legacy HR review' }}{{ isset($snapshot['version']) ? ' / Revision ' . $snapshot['version'] : '' }}</dd>
                <span class="detail-sub">Assigned approval routing</span>
            </div>
        </dl>

        {{-- 3. Two-Column Main Content: Reason & Attachment + Approval Progress --}}
        <div class="columns">
            <div class="columns-stack">
                <section class="band">
                    <div class="band-header">
                        <div class="band-title-wrap">
                            <x-filament::icon icon="heroicon-o-document-text" />
                            <h3>Reason</h3>
                        </div>
                        <span class="band-meta">Employee Statement</span>
                    </div>
                    <div class="band-body">
                        <div class="reason-box">
                            <p class="prose">{{ $leave->reason }}</p>
                        </div>
                    </div>
                </section>

                <section class="band">
                    <div class="band-header">
                        <div class="band-title-wrap">
                            <x-filament::icon icon="heroicon-o-paper-clip" />
                            <h3>Supporting Attachment</h3>
                        </div>
                        <span class="band-meta">{{ $leave->attachment_url ? 'Attached' : 'Optional' }}</span>
                    </div>
                    <div class="band-body">
                        @if ($leave->attachment_url)
                            <div class="attachment">
                                <div class="attachment-file">
                                    <x-filament::icon icon="heroicon-o-document-arrow-down" />
                                    <span>{{ $leave->attachment_name }}</span>
                                </div>
                                <x-filament::button
                                    tag="a"
                                    :href="$leave->attachment_url"
                                    target="_blank"
                                    rel="noopener"
                                    icon="heroicon-o-arrow-top-right-on-square"
                                    color="gray"
                                    size="sm"
                                    outlined
                                >
                                    View Attachment
                                </x-filament::button>
                            </div>
                        @else
                            <div class="empty-attachment">
                                <x-filament::icon icon="heroicon-o-document" />
                                <span class="muted">No attachment submitted.</span>
                            </div>
                        @endif
                    </div>
                </section>

                @if ($leave->hr_comment)
                    <section class="band">
                        <div class="band-header">
                            <div class="band-title-wrap">
                                <x-filament::icon icon="heroicon-o-chat-bubble-left-ellipsis" />
                                <h3>HR Remarks</h3>
                            </div>
                            <span class="band-meta">Review Note</span>
                        </div>
                        <div class="band-body">
                            <div class="reason-box">
                                <p class="prose">{{ $leave->hr_comment }}</p>
                            </div>
                        </div>
                    </section>
                @endif
            </div>

            <section class="band">
                <div class="band-header">
                    <div class="band-title-wrap">
                        <x-filament::icon icon="heroicon-o-check-badge" />
                        <h3>Approval Progress</h3>
                    </div>
                    <span class="band-meta">
                        {{ $leave->approvalSteps->count() ?: 1 }} {{ \Illuminate\Support\Str::plural('Step', $leave->approvalSteps->count() ?: 1) }}
                    </span>
                </div>
                <div class="band-body">
                    <ol class="timeline">
                        @forelse ($leave->approvalSteps as $step)
                            <li @class([
                                'current' => $step->status === 'Pending',
                                'is-approved' => $step->status === 'Approved',
                                'is-rejected' => $step->status === 'Rejected',
                            ])>
                                <div class="step-line">
                                    <span class="step-name">{{ $step->sequence }}. {{ $step->label }}</span>
                                    <x-filament::badge :color="$colors[$step->status] ?? 'gray'">
                                        {{ $step->status }}
                                    </x-filament::badge>
                                </div>
                                <div class="step-meta">
                                    <x-filament::icon icon="heroicon-m-user-circle" />
                                    <span class="muted">{{ $step->approver_name ?: 'Authorized HR reviewer' }}</span>
                                </div>
                                @if ($step->acted_at)
                                    <div class="step-meta" style="margin-top: 0.2rem;">
                                        <x-filament::icon icon="heroicon-m-clock" />
                                        <span class="muted">{{ $step->acted_at->format('M d, Y h:i A') }}</span>
                                    </div>
                                @endif
                                @if ($step->remarks)
                                    <div class="step-remarks">
                                        <p class="prose">{{ $step->remarks }}</p>
                                    </div>
                                @endif
                            </li>
                        @empty
                            <li @class([
                                'current' => $leave->status === 'Pending',
                                'is-approved' => $leave->status === 'Approved',
                                'is-rejected' => $leave->status === 'Rejected',
                            ])>
                                <div class="step-line">
                                    <span class="step-name">HR Review</span>
                                    <x-filament::badge :color="$colors[$leave->status] ?? 'gray'">
                                        {{ $leave->status }}
                                    </x-filament::badge>
                                </div>
                                <div class="step-meta">
                                    <x-filament::icon icon="heroicon-m-user-circle" />
                                    <span class="muted">{{ $leave->reviewedBy?->name ?: 'Authorized HR reviewer' }} {{ $leave->reviewed_at?->format('M d, Y h:i A') }}</span>
                                </div>
                            </li>
                        @endforelse
                    </ol>
                </div>
            </section>
        </div>

        {{-- 4. Activity History --}}
        @if ($leave->approvalEvents->isNotEmpty())
            <section class="band">
                <div class="band-header">
                    <div class="band-title-wrap">
                        <x-filament::icon icon="heroicon-o-clock" />
                        <h3>Activity History</h3>
                    </div>
                    <span class="band-meta">
                        {{ $leave->approvalEvents->count() }} {{ \Illuminate\Support\Str::plural('Record', $leave->approvalEvents->count()) }}
                    </span>
                </div>
                <div class="scroll">
                    <table class="audit">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>By</th>
                                <th>Date / Time</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($leave->approvalEvents as $event)
                                <tr>
                                    <td>
                                        <span class="audit-action-pill">{{ $event->action }}</span>
                                    </td>
                                    <td style="font-weight: 600;">{{ $event->actor_name }}</td>
                                    <td class="muted">{{ $event->created_at?->format('M d, Y h:i A') }}</td>
                                    <td>{{ $event->remarks ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
@endif
