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

        $workflowName = $snapshot['workflow'] ?? 'Default Approval Workflow';
        $workflowRev = $snapshot['version'] ?? 1;
    @endphp

    <div class="leave-review">
        {{-- 1. Employee & Request Overview Header --}}
        <header class="overview">
            <div class="overview-identity">
                <div class="overview-avatar" aria-hidden="true">{{ $initials }}</div>
                <div class="overview-info">
                    <h2>{{ $employeeName }}</h2>
                    <div class="overview-chips">
                        <span class="overview-chip code" title="Employee ID">
                            <x-filament::icon icon="heroicon-m-identification" />
                            {{ $companyId }}
                        </span>
                        <span class="overview-chip" title="Branch">
                            <x-filament::icon icon="heroicon-m-building-storefront" />
                            {{ $branchName }}
                        </span>
                        @if ($departmentName && $departmentName !== '-')
                            <span class="overview-chip" title="Department">
                                <x-filament::icon icon="heroicon-m-building-office" />
                                {{ $departmentName }}
                            </span>
                        @endif
                        @if ($designationTitle && $designationTitle !== '-')
                            <span class="overview-chip" title="Designation">
                                <x-filament::icon icon="heroicon-m-briefcase" />
                                {{ $designationTitle }}
                            </span>
                        @endif
                        <span class="overview-chip" title="Filing Timestamp">
                            <x-filament::icon icon="heroicon-m-paper-airplane" />
                            Submitted {{ $leave->created_at?->format('M d, Y h:i A') }} ({{ $leave->created_at?->diffForHumans() }})
                        </span>
                    </div>
                </div>
            </div>

            <div class="overview-status-box">
                <span class="overview-status-label">Current Status</span>
                <x-filament::badge :color="$colors[$leave->status] ?? 'gray'" size="lg">
                    {{ $leave->approval_label }}
                </x-filament::badge>
            </div>
        </header>

        {{-- 2. Two-Column Workspace: Core Request (Left) & Workflow / Balances (Right) --}}
        <div class="columns">
            {{-- Left Column: Core Request --}}
            <div class="columns-stack">
                {{-- A. Leave Details Hero Card --}}
                <section class="summary-hero">
                    <div class="summary-hero-header">
                        <div class="title-wrap">
                            <x-filament::icon icon="heroicon-o-calendar-days" />
                            <h3>Leave Request Details</h3>
                        </div>
                        <x-filament::badge color="info">
                            {{ $requestedDays }} {{ \Illuminate\Support\Str::plural('Day', $requestedDays) }}
                        </x-filament::badge>
                    </div>

                    <div class="summary-hero-grid">
                        <div class="summary-metric">
                            <span class="summary-metric-label">
                                <x-filament::icon icon="heroicon-o-tag" />
                                Leave Type
                            </span>
                            <div class="summary-metric-value">
                                <span>{{ $leave->leave_type }}</span>
                            </div>
                            <span class="summary-metric-sub">
                                {{ $leave->is_half_day ? 'Half-Day (' . ucfirst((string) $leave->half_day_period) . ' Period)' : 'Full-Day Leave Schedule' }}
                            </span>
                        </div>

                        <div class="summary-metric">
                            <span class="summary-metric-label">
                                <x-filament::icon icon="heroicon-o-calendar" />
                                Inclusive Dates
                            </span>
                            <div class="summary-metric-value">
                                @if ($leave->leave_from && $leave->leave_to && ! $leave->leave_from->isSameDay($leave->leave_to))
                                    <span>{{ $leave->leave_from->format('M d, Y') }}</span>
                                    <span style="opacity: 0.5;">–</span>
                                    <span>{{ $leave->leave_to->format('M d, Y') }}</span>
                                @elseif ($leave->leave_from)
                                    <span>{{ $leave->leave_from->format('M d, Y') }}</span>
                                @else
                                    <span>Dates unavailable</span>
                                @endif
                            </div>
                            <span class="summary-metric-sub">
                                @if ($leave->leave_from && $leave->leave_to)
                                    {{ $leave->leave_from->isSameDay($leave->leave_to) ? $leave->leave_from->format('l') : ($leave->leave_from->format('l') . ' to ' . $leave->leave_to->format('l')) }}
                                @else
                                    Scheduled leave period
                                @endif
                            </span>
                        </div>

                        <div class="summary-metric">
                            <span class="summary-metric-label">
                                <x-filament::icon icon="heroicon-o-clock" />
                                Duration & Schedule
                            </span>
                            <div class="summary-metric-value">
                                <span>{{ $requestedDays }} {{ \Illuminate\Support\Str::plural('Day', $requestedDays) }}</span>
                                @if ($leave->is_half_day)
                                    <x-filament::badge color="gray" size="sm">
                                        {{ ucfirst((string) $leave->half_day_period) }}
                                    </x-filament::badge>
                                @endif
                            </div>
                            <span class="summary-metric-sub">
                                {{ $leave->is_half_day ? 'Half-day schedule (' . ucfirst((string) $leave->half_day_period) . ')' : 'Standard whole-day leave' }}
                            </span>
                        </div>

                        <div class="summary-metric">
                            <span class="summary-metric-label">
                                <x-filament::icon icon="heroicon-o-banknotes" />
                                Pay Treatment
                            </span>
                            <div class="summary-metric-value">
                                <x-filament::badge :color="$leave->isPaid() ? 'success' : 'gray'" size="md">
                                    {{ $leave->isPaid() ? 'Paid Leave' : 'Unpaid Leave' }}
                                </x-filament::badge>
                            </div>
                            <span class="summary-metric-sub">
                                {{ $leave->isPaid() ? 'Credited with pay upon final approval' : 'Recorded without pay credit' }}
                            </span>
                        </div>
                    </div>

                    @if ($leave->half_day_schedule)
                        <div class="summary-sub-banner">
                            <x-filament::icon icon="heroicon-o-information-circle" />
                            <span>Daily Rate Schedule: <strong>{{ $leave->half_day_schedule }}</strong></span>
                        </div>
                    @endif
                </section>

                {{-- B. Reason & Supporting Documents Card --}}
                <section class="band">
                    <div class="band-header">
                        <div class="band-title-wrap">
                            <x-filament::icon icon="heroicon-o-document-text" />
                            <h3>Reason for Leave</h3>
                        </div>
                        <span class="band-meta">Employee Statement</span>
                    </div>
                    <div class="band-body">
                        <div class="reason-box">
                            <p class="prose">{{ $leave->reason }}</p>
                        </div>

                        <div style="margin-top: 1rem; padding-top: 0.85rem; border-top: 1px solid var(--lr-border);">
                            <div style="font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: var(--lr-text-muted); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.35rem;">
                                <x-filament::icon icon="heroicon-o-paper-clip" style="width: 0.85rem; height: 0.85rem;" />
                                Supporting Document
                            </div>
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
                                        color="primary"
                                        size="sm"
                                        outlined
                                    >
                                        View Attachment
                                    </x-filament::button>
                                </div>
                            @else
                                <div class="empty-attachment">
                                    <x-filament::icon icon="heroicon-o-document" />
                                    <span>No attachment uploaded with this request.</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </section>

                {{-- C. HR Remarks (If present) --}}
                @if ($leave->hr_comment)
                    <section class="band">
                        <div class="band-header">
                            <div class="band-title-wrap">
                                <x-filament::icon icon="heroicon-o-chat-bubble-left-ellipsis" />
                                <h3>HR Review Remarks</h3>
                            </div>
                            <span class="band-meta">Official Note</span>
                        </div>
                        <div class="band-body">
                            <div class="reason-box" style="border-inline-start-color: #10b981;">
                                <p class="prose">{{ $leave->hr_comment }}</p>
                            </div>
                        </div>
                    </section>
                @endif
            </div>

            {{-- Right Column: Workflow, Approvals & Leave Balances --}}
            <div class="columns-stack">
                {{-- A. Approval Progress Card --}}
                <section class="band">
                    <div class="band-header">
                        <div class="band-title-wrap">
                            <x-filament::icon icon="heroicon-o-check-badge" />
                            <h3>Approval Progress</h3>
                        </div>
                        <span class="band-meta" title="Configured approval workflow">
                            {{ $workflowName }} (Rev {{ $workflowRev }})
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
                                        <x-filament::badge :color="$colors[$step->status] ?? 'gray'" size="sm">
                                            {{ $step->status }}
                                        </x-filament::badge>
                                    </div>
                                    <div class="step-meta">
                                        <x-filament::icon icon="heroicon-m-user-circle" />
                                        <span class="muted">{{ $step->approver_name ?: 'Authorized HR reviewer' }}</span>
                                    </div>
                                    @if ($step->acted_at)
                                        <div class="step-meta" style="margin-top: 0.25rem;">
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
                                        <x-filament::badge :color="$colors[$leave->status] ?? 'gray'" size="sm">
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

                {{-- B. Employee Leave Balances Snapshot --}}
                @if ($leave->employee)
                    <section class="band">
                        <div class="band-header">
                            <div class="band-title-wrap">
                                <x-filament::icon icon="heroicon-o-chart-pie" />
                                <h3>Leave Balances</h3>
                            </div>
                            <span class="band-meta">Available Credits</span>
                        </div>
                        <div class="band-body">
                            <div class="balances-grid">
                                <div class="balance-tile">
                                    <div class="balance-number">{{ number_format((float) ($leave->employee->leave_credits ?? 0), 1) }}</div>
                                    <div class="balance-label">General Credits</div>
                                </div>
                                <div class="balance-tile">
                                    <div class="balance-number" style="color: #10b981;">{{ number_format((float) ($leave->employee->birthday_leave_credits ?? 0), 0) }}</div>
                                    <div class="balance-label">Birthday Leave</div>
                                </div>
                            </div>
                        </div>
                    </section>
                @endif
            </div>
        </div>

        {{-- 3. Activity History (Full Width at Bottom) --}}
        @if ($leave->approvalEvents->isNotEmpty())
            <section class="band" style="margin-top: 0.25rem;">
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
