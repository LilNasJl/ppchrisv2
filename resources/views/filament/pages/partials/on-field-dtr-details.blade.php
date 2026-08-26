@php
    $statusColor = match ($submission->status) {
        \App\Models\DtrSubmission::STATUS_APPROVED => 'success',
        \App\Models\DtrSubmission::STATUS_REJECTED => 'danger',
        default => 'warning',
    };
@endphp

<div class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="border-b border-gray-200 pb-3 dark:border-white/10">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Employee Name</p>
            <p class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $submission->submittedEmployeeName() }}</p>
        </div>
        <div class="border-b border-gray-200 pb-3 dark:border-white/10">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Employee ID</p>
            <p class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $submission->employee_company_id_snapshot ?: 'Not available' }}</p>
        </div>
        <div class="border-b border-gray-200 pb-3 dark:border-white/10">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Branch</p>
            <p class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $submission->submittedBranchName() }}</p>
        </div>
        <div class="border-b border-gray-200 pb-3 dark:border-white/10">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Status</p>
            <div class="mt-1">
                <x-filament::badge :color="$statusColor">{{ $submission->status }}</x-filament::badge>
            </div>
        </div>
        <div class="border-b border-gray-200 pb-3 dark:border-white/10">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Payroll Period</p>
            <p class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $submission->payrollPeriod?->title ?: 'Not available' }}</p>
        </div>
        <div class="border-b border-gray-200 pb-3 dark:border-white/10">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Date Submitted</p>
            <p class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $submission->created_at?->format('M d, Y h:i A') }}</p>
        </div>
        <div class="border-b border-gray-200 pb-3 dark:border-white/10">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Submitted By</p>
            <p class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $submission->submittedEmployeeName() }}</p>
        </div>
        <div class="border-b border-gray-200 pb-3 dark:border-white/10">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Request ID</p>
            <p class="mt-1 break-all font-mono text-sm text-gray-950 dark:text-white">{{ $submission->publicKey() }}</p>
        </div>
    </div>

    <section>
        <h2 class="text-base font-semibold text-gray-950 dark:text-white">DTR Entries</h2>
        <div class="mt-3 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                'Date In' => $submission->date_in?->format('M d, Y'),
                'Time In' => filled($submission->time_in) ? \Carbon\Carbon::parse($submission->time_in)->format('h:i A') : null,
                'Date Out' => $submission->date_out?->format('M d, Y'),
                'Time Out' => filled($submission->time_out) ? \Carbon\Carbon::parse($submission->time_out)->format('h:i A') : null,
            ] as $label => $value)
                <div class="border-b border-gray-200 pb-3 dark:border-white/10">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
                    <p class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $value ?: 'Not available' }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-2">
        <div>
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Proof File</h2>
            <p class="mt-2 break-all text-sm text-gray-700 dark:text-gray-300">{{ $submission->file_name }}</p>
            @auth
                <div class="mt-3 flex flex-wrap gap-2">
                    <x-filament::button
                        tag="a"
                        :href="route('hr_tools.dtr_submissions.view', $submission)"
                        target="_blank"
                        icon="heroicon-m-eye"
                        color="gray"
                    >
                        View
                    </x-filament::button>
                    <x-filament::button
                        tag="a"
                        :href="route('hr_tools.dtr_submissions.download', $submission)"
                        target="_blank"
                        icon="heroicon-m-arrow-down-tray"
                    >
                        Download
                    </x-filament::button>
                </div>
            @endauth
        </div>
        <div>
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Description</h2>
            <p class="mt-2 whitespace-pre-wrap text-sm leading-6 text-gray-700 dark:text-gray-300">{{ $submission->description ?: 'No description provided.' }}</p>
        </div>
    </section>

    @if (($showReviewer ?? false) && $submission->reviewed_at)
        <section class="border-t border-gray-200 pt-5 dark:border-white/10">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">HR Review</h2>
            <div class="mt-3 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Reviewed By</p>
                    <p class="mt-1 text-sm text-gray-950 dark:text-white">{{ $submission->reviewedBy?->username ?: 'Account no longer available' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Reviewed At</p>
                    <p class="mt-1 text-sm text-gray-950 dark:text-white">{{ $submission->reviewed_at->format('M d, Y h:i A') }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Generated DTR</p>
                    <p class="mt-1 text-sm text-gray-950 dark:text-white">
                        {{ $submission->generated_dtr_id ? 'Linked record #'.$submission->generated_dtr_id : 'None' }}
                    </p>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">HR Remarks</p>
                <p class="mt-1 whitespace-pre-wrap text-sm text-gray-950 dark:text-white">{{ $submission->reviewer_remarks ?: 'No remarks.' }}</p>
            </div>
        </section>
    @endif
</div>
