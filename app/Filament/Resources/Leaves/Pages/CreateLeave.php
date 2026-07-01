<?php

namespace App\Filament\Resources\Leaves\Pages;

use App\Filament\Resources\Leaves\LeaveResource;
use App\Models\Employee;
use App\Models\Leave;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CreateLeave extends CreateRecord
{
    protected static string $resource = LeaveResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ((bool) ($data['is_half_day'] ?? false)) {
            $data['leave_type'] = Leave::HALF_DAY_LEAVE;
        }

        $employee = Employee::query()->find($data['employee_id'] ?? null);

        if (! $employee) {
            throw ValidationException::withMessages([
                'data.employee_id' => 'Select a valid employee.',
            ]);
        }

        try {
            Leave::validateCanCreateRequest(
                $employee,
                (string) $data['leave_type'],
                $data['leave_from'],
                $data['leave_to'],
                (bool) ($data['is_half_day'] ?? false),
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'data.leave_type' => $exception->getMessage(),
            ]);
        }

        $data['status'] ??= 'Pending';

        return $data;
    }
}
