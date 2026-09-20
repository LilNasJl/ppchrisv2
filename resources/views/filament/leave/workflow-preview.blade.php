@include('filament.leave.styles')
@if ($employee)
    <div class="leave-review">
        <h3>Approval Route</h3>
        @php
            try {
                [$flow, $route] = app(\App\Services\LeaveApprovalService::class)->resolve($employee);
                $routeError = null;
            } catch (\RuntimeException $exception) {
                $routeError = $exception->getMessage();
            }
        @endphp
        @if ($routeError)
            <p class="prose" role="alert">{{ $routeError }}</p>
        @else
            <p class="muted">{{ $flow->name }} / Revision {{ $flow->version }}</p>
            <div class="route">
                @foreach ($route as $step)
                    <x-filament::badge color="gray">{{ $loop->iteration }}. {{ $step['label'] }}{{ $step['approver_name'] ? ': '.$step['approver_name'] : '' }}</x-filament::badge>
                @endforeach
            </div>
        @endif
    </div>
@endif
