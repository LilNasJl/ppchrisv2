@include('filament.leave.styles')
@if ($employee)
    @php
        try {
            [$flow, $route] = app(\App\Services\LeaveApprovalService::class)->resolve($employee);
            $routeError = null;
        } catch (\RuntimeException $exception) {
            $routeError = $exception->getMessage();
        }
    @endphp
    <div class="workflow-preview-card">
        <div class="workflow-preview-header">
            <div class="workflow-preview-title">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-11.25a.75.75 0 0 0-1.5 0v4.59l-2.22-2.22a.75.75 0 1 0-1.06 1.06l3.5 3.5a.75.75 0 0 0 1.06 0l3.5-3.5a.75.75 0 0 0-1.06-1.06l-2.22 2.22V6.75Z" clip-rule="evenodd" />
                </svg>
                <span>Approval Route</span>
            </div>
            @if (! $routeError && isset($flow))
                <div class="workflow-preview-badge">
                    <span>{{ $flow->name }}</span>
                    <span style="opacity: 0.7;">(Rev {{ $flow->version }})</span>
                </div>
            @endif
        </div>
        @if ($routeError)
            <p class="prose" role="alert" style="color: #ef4444; font-size: 11.5px; margin-top: 6px;">{{ $routeError }}</p>
        @else
            <div class="workflow-preview-steps">
                @foreach ($route as $step)
                    <div class="workflow-preview-step {{ ($step['skip_self'] ?? false) ? 'is-skipped' : '' }}">
                        <span class="workflow-preview-step-num">{{ $loop->iteration }}</span>
                        <span class="workflow-preview-step-label">{{ $step['label'] }}</span>
                        @if ($step['skip_self'] ?? false)
                            <span class="workflow-preview-step-approver" style="opacity: 0.85; font-size: 0.68rem;">(Skipped - own request)</span>
                        @elseif ($step['approver_name'] ?? null)
                            <span style="opacity: 0.5;">:</span>
                            <span class="workflow-preview-step-approver">{{ $step['approver_name'] }}</span>
                        @endif
                    </div>
                    @if (! $loop->last)
                        <div class="workflow-preview-arrow" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
@endif
