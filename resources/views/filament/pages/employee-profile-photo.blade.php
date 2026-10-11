@php
    $record = $getRecord();
    $incompleteCount = app(\App\Services\EmployeeProfileCompletionService::class)->total($record->employee);
@endphp

<div style="position: relative; display: block; width: 40px; height: 40px; margin: 0 auto;" title="{{ $incompleteCount > 0 ? $incompleteCount . ' incomplete required profile fields' : 'Profile complete' }}">
    <img src="{{ $record->profile_photo_url ?: asset('image/ppc-circle-white.png') }}" alt="{{ $record->employee?->full_name ?? 'Employee' }} profile" style="display: block; width: 40px; height: 40px; max-width: none; border-radius: 50%; object-fit: cover;">
    @if ($incompleteCount > 0)
        <span style="position: absolute; top: -7px; right: -9px; display: inline-flex; min-width: 20px; height: 20px; padding: 0 4px; align-items: center; justify-content: center; border: 2px solid #111827; border-radius: 9999px; background: #f59e0b; color: #111827; font-size: 11px; font-weight: 700; line-height: 1; box-sizing: border-box; z-index: 1;">{{ $incompleteCount }}</span>
    @endif
</div>
