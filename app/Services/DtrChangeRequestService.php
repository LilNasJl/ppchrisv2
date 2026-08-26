<?php

namespace App\Services;

use App\Models\DtrChangeRequest;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\SicRcAccount;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DtrChangeRequestService
{
    public function submit(Employee $employee, array $data): DtrChangeRequest
    {
        return DB::transaction(function () use ($employee, $data): DtrChangeRequest {
            $employee = Employee::query()
                ->with('branch')
                ->whereKey($employee->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (blank($employee->branch_id) || ! $employee->branch) {
                throw ValidationException::withMessages([
                    'payroll_period_id' => 'Your employee profile is not assigned to a branch.',
                ]);
            }

            $period = PayrollPeriod::query()->find($data['payroll_period_id'] ?? null);

            if (! $period) {
                throw ValidationException::withMessages([
                    'payroll_period_id' => 'Select a valid payroll period.',
                ]);
            }

            $dateFrom = $this->parseDate($data['date_from'] ?? null, 'date_from');
            $dateTo = $this->parseDate($data['date_to'] ?? null, 'date_to');

            if ($dateTo->lessThan($dateFrom)) {
                throw ValidationException::withMessages([
                    'date_to' => 'Date To cannot be earlier than Date From.',
                ]);
            }

            if ($dateFrom->lessThan($period->date_start) || $dateTo->greaterThan($period->date_end)) {
                throw ValidationException::withMessages([
                    'date_from' => 'The selected dates must be within '.$period->title.'.',
                    'date_to' => 'The selected dates must be within '.$period->title.'.',
                ]);
            }

            $requestType = (string) ($data['request_type'] ?? '');
            if (! array_key_exists($requestType, DtrChangeRequest::requestTypeOptions())) {
                throw ValidationException::withMessages([
                    'request_type' => 'Select a valid change request type.',
                ]);
            }

            $description = trim((string) ($data['description'] ?? ''));
            if ($description === '') {
                throw ValidationException::withMessages([
                    'description' => 'Describe the D.T.R issue or requested correction.',
                ]);
            }

            if (mb_strlen($description) > 2000) {
                throw ValidationException::withMessages([
                    'description' => 'The description must not exceed 2,000 characters.',
                ]);
            }

            $owner = $this->branchOwner((int) $employee->branch_id);

            $duplicateExists = DtrChangeRequest::query()
                ->where('employee_id', $employee->id)
                ->where('payroll_period_id', $period->id)
                ->whereDate('date_from', $dateFrom)
                ->whereDate('date_to', $dateTo)
                ->where('request_type', $requestType)
                ->pending()
                ->exists();

            if ($duplicateExists) {
                throw ValidationException::withMessages([
                    'request_type' => 'An identical pending D.T.R change request already exists.',
                ]);
            }

            return DtrChangeRequest::query()->create([
                'employee_id' => $employee->id,
                'branch_id' => $employee->branch_id,
                'payroll_period_id' => $period->id,
                'assigned_sic_rc_account_id' => $owner->id,
                'employee_name_snapshot' => $employee->full_name,
                'employee_company_id_snapshot' => $employee->company_id,
                'branch_name_snapshot' => $employee->branch->branch_name,
                'payroll_period_title_snapshot' => $period->title,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'request_type' => $requestType,
                'description' => $description,
                'status' => DtrChangeRequest::STATUS_PENDING,
            ]);
        });
    }

    public function approve(DtrChangeRequest $request, SicRcAccount $reviewer, ?string $remarks = null): DtrChangeRequest
    {
        return $this->review($request, $reviewer, DtrChangeRequest::STATUS_APPROVED, $remarks);
    }

    public function reject(DtrChangeRequest $request, SicRcAccount $reviewer, string $remarks): DtrChangeRequest
    {
        return $this->review($request, $reviewer, DtrChangeRequest::STATUS_REJECTED, $remarks);
    }

    protected function review(
        DtrChangeRequest $request,
        SicRcAccount $reviewer,
        string $status,
        ?string $remarks,
    ): DtrChangeRequest {
        return DB::transaction(function () use ($request, $reviewer, $status, $remarks): DtrChangeRequest {
            $request = DtrChangeRequest::query()
                ->whereKey($request->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $reviewer->is_active || $reviewer->trashed() || ! in_array((int) $request->branch_id, $reviewer->assignedBranchIds(), true)) {
                throw new AuthorizationException('This D.T.R change request does not belong to one of your assigned branches.');
            }

            if ($request->status !== DtrChangeRequest::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'status' => 'Only pending D.T.R change requests can be reviewed.',
                ]);
            }

            $remarks = trim((string) $remarks);
            if (mb_strlen($remarks) > 2000) {
                throw ValidationException::withMessages([
                    'reviewer_remarks' => 'The SIC/RC remarks must not exceed 2,000 characters.',
                ]);
            }

            if ($status === DtrChangeRequest::STATUS_REJECTED && $remarks === '') {
                throw ValidationException::withMessages([
                    'reviewer_remarks' => 'Explain why the D.T.R change request is being rejected.',
                ]);
            }

            $request->update([
                'status' => $status,
                'reviewed_by_sic_rc_account_id' => $reviewer->id,
                'reviewer_remarks' => $remarks !== '' ? $remarks : null,
                'reviewed_at' => now(),
            ]);

            return $request->refresh();
        });
    }

    protected function branchOwner(int $branchId): SicRcAccount
    {
        $owners = SicRcAccount::query()
            ->where('is_active', true)
            ->get()
            ->filter(fn (SicRcAccount $account): bool => in_array($branchId, $account->assignedBranchIds(), true))
            ->values();

        if ($owners->isEmpty()) {
            throw ValidationException::withMessages([
                'payroll_period_id' => 'No active SIC/RC account is assigned to your branch. Contact HR before submitting this request.',
            ]);
        }

        if ($owners->count() > 1) {
            throw ValidationException::withMessages([
                'payroll_period_id' => 'Your branch has conflicting SIC/RC assignments. Contact HR before submitting this request.',
            ]);
        }

        return $owners->first();
    }

    protected function parseDate(mixed $value, string $field): CarbonImmutable
    {
        if (blank($value)) {
            throw ValidationException::withMessages([
                $field => 'Enter a valid date.',
            ]);
        }

        try {
            return CarbonImmutable::parse((string) $value)->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                $field => 'Enter a valid date.',
            ]);
        }
    }
}
