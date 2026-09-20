@include('filament.leave.styles')
<div class="leave-review">
    @foreach ($revisions as $revision)
        @php($configuration = json_decode($revision->configuration, true))
        <section class="band">
            <h3>Revision {{ $revision->version }}: {{ $configuration['name'] ?? '' }}</h3>
            <p class="muted">{{ $revision->created_at }} / Account {{ $revision->actor_id ?? 'System' }}</p>
            <div class="route">
                @foreach ($configuration['levels'] ?? [] as $level)
                    <x-filament::badge color="gray">{{ $level['label'] }} / Employee {{ $level['approver_employee_id'] }}</x-filament::badge>
                @endforeach
                <x-filament::badge color="primary">HR</x-filament::badge>
            </div>
        </section>
    @endforeach
</div>
