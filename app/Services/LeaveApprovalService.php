<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveApprovalEvent;
use App\Models\LeaveApprovalWorkflow;
use App\Models\LeaveRequestApproval;
use App\Models\PayrollPeriod;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class LeaveApprovalService
{
    public function resolve(Employee $employee): array
    {
        $matches = LeaveApprovalWorkflow::with('levels')->where('is_active', true);
        foreach (['employee' => $employee->id, 'branch' => $employee->branch_id,
            'department' => $employee->department_id, 'designation' => $employee->designation_id] as $key => $value) {
            $matches->where(fn ($q) => $q->whereNull($key.'_id')->orWhere($key.'_id', $value));
        }
        $matches = $matches->get()->sortByDesc(fn ($flow) => $flow->specificity())->values();
        $flow = $matches->first();
        if (! $flow || ($matches->count() > 1 && $flow->specificity() === $matches[1]->specificity())) {
            throw new RuntimeException('No unambiguous leave workflow is available. Please contact HR.');
        }
        $steps = [];
        foreach ($flow->levels as $level) {
            $approver = Employee::with('user')->find($level->approver_employee_id);
            if (! LeaveApprovalAccess::employee($approver) || (int) $approver->id === (int) $employee->id) {
                $approver = Employee::with('user')->find($level->alternate_employee_id);
            }
            if (! LeaveApprovalAccess::employee($approver) || (int) $approver->id === (int) $employee->id) {
                throw new RuntimeException('The '.$level->label.' level needs an available alternate approver. Please contact HR.');
            }
            $steps[] = ['label' => $level->label, 'approver_employee_id' => $approver->id,
                'approver_name' => $approver->full_name, 'is_hr' => false];
        }
        $steps[] = ['label' => 'HR', 'approver_employee_id' => null, 'approver_name' => null, 'is_hr' => true];

        return [$flow, $steps];
    }

    public function submit(Employee $employee, array $data, User $actor): Leave
    {
        abort_unless(LeaveApprovalAccess::review($actor)
            || ($actor->role === 'employee' && ! $actor->is_disabled && (int) $actor->employee?->id === (int) $employee->id), 403);

        return DB::transaction(function () use ($employee, $data, $actor): Leave {
            $employee = Employee::with(['user', 'branch', 'department', 'designation'])->lockForUpdate()->findOrFail($employee->id);
            if ($employee->hasEndedEmployment()) {
                throw new RuntimeException('Leave requests cannot be submitted for ended employment.');
            }
            [$flow, $steps] = $this->resolve($employee);
            $flow = LeaveApprovalWorkflow::lockForUpdate()->findOrFail($flow->id);
            // Resolve again under the configuration lock so submission cannot mix two revisions.
            [$flow, $steps] = $this->resolve($employee);
            $data = Arr::only($data, ['leave_type', 'leave_from', 'leave_to', 'is_half_day', 'half_day_period',
                'half_day_schedule', 'reason', 'attachment_path', 'attachment_original_name']);
            validator($data, [
                'leave_type' => 'required|string|max:255', 'leave_from' => 'required|date',
                'leave_to' => 'required|date|after_or_equal:leave_from', 'reason' => 'required|string|max:255',
                'is_half_day' => 'sometimes|boolean',
                'half_day_period' => 'required_if:is_half_day,1|nullable|in:morning,afternoon',
            ])->validate();
            Leave::validateCanCreateRequest($employee, $data['leave_type'], $data['leave_from'], $data['leave_to'], (bool) ($data['is_half_day'] ?? false));

            $overlaps = Leave::where('employee_id', $employee->id)->whereIn('status', ['Pending', 'Approved'])
                ->whereDate('leave_from', '<=', $data['leave_to'])->whereDate('leave_to', '>=', $data['leave_from'])->get();
            foreach ($overlaps as $other) {
                if (! ($other->is_half_day && ($data['is_half_day'] ?? false)
                    && $other->half_day_period !== ($data['half_day_period'] ?? null))) {
                    throw new RuntimeException('An existing pending or approved leave overlaps these dates.');
                }
            }
            $leave = new Leave($data);
            $leave->forceFill([
                'employee_id' => $employee->id, 'status' => 'Pending',
                'approval_workflow_id' => $flow->id, 'approval_workflow_version' => $flow->version,
                'approval_phase' => $steps[0]['is_hr'] ? 'hr' : 'preliminary', 'current_approval_order' => 1,
                'approval_snapshot' => [
                    'workflow' => $flow->name, 'version' => $flow->version, 'steps' => $steps,
                    'employee_name' => $employee->full_name, 'employee_company_id' => $employee->company_id,
                    'branch_id' => $employee->branch_id, 'branch' => $employee->branch?->branch_name,
                    'department' => $employee->department?->name, 'designation' => $employee->designation?->title,
                ],
            ])->save();
            foreach ($steps as $index => $step) {
                $leave->approvalSteps()->create($step + ['sequence' => $index + 1,
                    'status' => $index === 0 ? 'Pending' : 'Waiting', 'activated_at' => $index === 0 ? now() : null]);
            }
            $event = $this->event($leave, $actor, 'Submitted', null);
            app(LeaveApprovalNotifier::class)->current($leave, $event);

            return $leave;
        });
    }

    public function decide(Leave $request, int $expectedStep, User $actor, bool $approve, ?string $remarks = null): void
    {
        DB::transaction(function () use ($request, $expectedStep, $actor, $approve, $remarks): void {
            $leave = $this->pending($request);
            $step = $this->current($leave);
            abort_unless($actor->role === 'employee' && LeaveApprovalAccess::employee($actor->employee)
                && ! $step->is_hr && (int) $step->approver_employee_id === (int) $actor->employee->id
                && (int) $leave->employee_id !== (int) $actor->employee->id, 403);
            if ($step->id !== $expectedStep) {
                throw new RuntimeException('This approval has changed. Refresh the request before continuing.');
            }
            $this->remarks($remarks, ! $approve);
            $step->update(['status' => $approve ? 'Approved' : 'Rejected', 'acted_by' => $actor->id, 'acted_at' => now(), 'remarks' => $remarks]);
            $event = $this->event($leave, $actor, $approve ? 'Approved '.$step->label : 'Rejected '.$step->label, $remarks, $step);
            if ($approve) {
                $this->advance($leave, $step, $event);
            } else {
                $this->stop($leave, 'Rejected');
                app(LeaveApprovalNotifier::class)->requester($leave, $event, 'Leave request rejected');
            }
        });
    }

    public function decideHr(Leave $request, User $actor, bool $approve, ?PayrollPeriod $period, ?string $remarks): void
    {
        abort_unless(LeaveApprovalAccess::review($actor), 403);
        DB::transaction(function () use ($request, $actor, $approve, $period, $remarks): void {
            $leave = $this->pending($request);
            if (! $leave->isReadyForHr()) {
                throw new RuntimeException('All preliminary levels must be completed before HR review.');
            }
            abort_if((int) $actor->employee?->id === (int) $leave->employee_id, 403, 'You cannot review your own leave.');
            $this->remarks($remarks, ! $approve);
            $step = $leave->approval_workflow_id ? $this->current($leave) : null;
            if ($step && ! $step->is_hr) {
                throw new RuntimeException('The current step is not HR.');
            }
            if ($approve) {
                if (! $period) {
                    throw new RuntimeException('Select an open payroll period.');
                }
                app(LeaveDtrService::class)->approveLeaveWithPaidDtr($leave, $period, $remarks, $actor->id);
            } else {
                $leave->rejectRequest($remarks, $actor->id);
            }
            $step?->update(['status' => $approve ? 'Approved' : 'Rejected', 'acted_by' => $actor->id, 'acted_at' => now(), 'remarks' => $remarks]);
            $leave->forceFill(['approval_phase' => 'complete', 'current_approval_order' => null])->save();
            $event = $this->event($leave, $actor, $approve ? 'Final HR approval' : 'HR rejection', $remarks, $step);
            app(LeaveApprovalNotifier::class)->requester($leave, $event, $approve ? 'Leave request approved' : 'Leave request rejected');
        });
    }

    public function cancel(Leave $request, User $actor): void
    {
        DB::transaction(function () use ($request, $actor): void {
            $leave = $this->pending($request);
            abort_unless(! $actor->is_disabled && $actor->role === 'employee'
                && (int) $actor->employee?->id === (int) $leave->employee_id, 403);
            $step = $leave->approvalSteps()->where('status', 'Pending')->first();
            $event = $this->event($leave, $actor, 'Cancelled', 'Cancelled by the requesting employee.', $step);
            app(LeaveApprovalNotifier::class)->current($leave, $event, 'Leave request cancelled');
            $this->stop($leave, 'Cancelled');
        });
    }

    public function reassign(Leave $request, int $expectedStep, Employee $replacement, User $actor, string $reason): void
    {
        abort_unless(LeaveApprovalAccess::override($actor), 403);
        DB::transaction(function () use ($request, $expectedStep, $replacement, $actor, $reason): void {
            $leave = $this->pending($request);
            $step = $this->current($leave);
            if ($step->id !== $expectedStep || $step->is_hr) {
                throw new RuntimeException('The approval step has changed or is already at HR.');
            }
            $replacement = Employee::with('user')->findOrFail($replacement->id);
            if (! LeaveApprovalAccess::employee($replacement) || (int) $replacement->id === (int) $leave->employee_id) {
                throw new RuntimeException('Select an active employee other than the requester.');
            }
            $this->remarks($reason, true);
            $previous = $step->approver_employee_id;
            $step->update(['approver_employee_id' => $replacement->id, 'approver_name' => $replacement->full_name,
                'activated_at' => now(), 'reminded_at' => null]);
            $event = $this->event($leave, $actor, 'Approver reassigned', $reason, $step,
                ['previous_employee_id' => $previous, 'replacement_employee_id' => $replacement->id]);
            app(LeaveApprovalNotifier::class)->current($leave, $event);
            app(LeaveApprovalNotifier::class)->requester($leave, $event, 'Leave approver reassigned');
        });
    }

    public function skip(Leave $request, int $expectedStep, User $actor, string $reason): void
    {
        abort_unless(LeaveApprovalAccess::override($actor), 403);
        DB::transaction(function () use ($request, $expectedStep, $actor, $reason): void {
            $leave = $this->pending($request);
            abort_if((int) $actor->employee?->id === (int) $leave->employee_id, 403);
            $step = $this->current($leave);
            if ($step->id !== $expectedStep || $step->is_hr) {
                throw new RuntimeException('Only the current preliminary step can be skipped.');
            }
            $this->remarks($reason, true);
            $step->update(['status' => 'Skipped', 'remarks' => $reason, 'acted_by' => $actor->id, 'acted_at' => now()]);
            $event = $this->event($leave, $actor, 'Level skipped: '.$step->label, $reason, $step);
            $this->advance($leave, $step, $event);
            app(LeaveApprovalNotifier::class)->requester($leave, $event, 'Leave approval route updated');
        });
    }

    protected function advance(Leave $leave, LeaveRequestApproval $step, LeaveApprovalEvent $event): void
    {
        $next = $leave->approvalSteps()->where('sequence', $step->sequence + 1)->firstOrFail();
        $next->update(['status' => 'Pending', 'activated_at' => now()]);
        $leave->forceFill(['current_approval_order' => $next->sequence, 'approval_phase' => $next->is_hr ? 'hr' : 'preliminary'])->save();
        app(LeaveApprovalNotifier::class)->current($leave, $event);
    }

    protected function stop(Leave $leave, string $status): void
    {
        $leave->approvalSteps()->whereIn('status', ['Pending', 'Waiting'])->update(['status' => 'Cancelled']);
        $leave->forceFill(['status' => $status, 'approval_phase' => 'complete', 'current_approval_order' => null, 'status_updated_at' => now()])->save();
    }

    protected function pending(Leave $request): Leave
    {
        $leave = Leave::lockForUpdate()->findOrFail($request->id);
        if ($leave->status !== 'Pending') {
            throw new RuntimeException('This request is no longer pending. Refresh to see the latest decision.');
        }

        return $leave;
    }

    protected function current(Leave $leave): LeaveRequestApproval
    {
        return $leave->approvalSteps()->where('sequence', $leave->current_approval_order)->where('status', 'Pending')->lockForUpdate()->firstOrFail();
    }

    protected function remarks(?string $remarks, bool $required): void
    {
        if (($required && blank($remarks)) || mb_strlen((string) $remarks) > 2000) {
            throw new RuntimeException('Enter a reason of up to 2,000 characters.');
        }
    }

    public function event(Leave $leave, ?User $actor, string $action, ?string $remarks, ?LeaveRequestApproval $step = null, array $details = []): LeaveApprovalEvent
    {
        return $leave->approvalEvents()->create([
            'step_id' => $step?->id, 'actor_id' => $actor?->id,
            'actor_name' => $actor?->employee?->full_name ?: ($actor?->name ?: 'System'),
            'action' => $action, 'remarks' => $remarks, 'details' => $details,
        ]);
    }

    public function saveWorkflow(array $data, User $actor, ?LeaveApprovalWorkflow $workflow = null): LeaveApprovalWorkflow
    {
        abort_unless(LeaveApprovalAccess::configure($actor), 403);
        validator($data, ['name' => 'required|string|max:150', 'levels' => 'present|array|max:20',
            'levels.*.label' => 'required|string|max:100', 'levels.*.approver_employee_id' => 'required|integer',
            'is_active' => 'required|boolean'])->validate();

        return DB::transaction(function () use ($data, $actor, $workflow): LeaveApprovalWorkflow {
            $workflow = $workflow ? LeaveApprovalWorkflow::lockForUpdate()->findOrFail($workflow->id) : new LeaveApprovalWorkflow;
            if ($workflow->exists && isset($data['version']) && (int) $data['version'] !== (int) $workflow->version) {
                throw ValidationException::withMessages(['name' => 'This workflow changed. Reopen it before saving.']);
            }
            $attributes = Arr::only($data, ['name', 'is_active']);
            foreach (['employee' => Employee::class, 'branch' => \App\Models\Branch::class,
                'department' => \App\Models\Department::class, 'designation' => \App\Models\Designation::class] as $scope => $model) {
                $id = $data[$scope.'_id'] ?? null;
                if ($id && ! $model::whereKey($id)->exists()) {
                    throw ValidationException::withMessages([$scope.'_id' => 'Select an available '.$scope.'.']);
                }
                $attributes[$scope.'_id'] = $id ?: null;
            }
            foreach ($data['levels'] as $level) {
                foreach (['approver_employee_id', 'alternate_employee_id'] as $field) {
                    if (filled($level[$field] ?? null) && ! LeaveApprovalAccess::employee(Employee::with('user')->find($level[$field]))) {
                        throw ValidationException::withMessages(['levels' => 'Every approver must have an active Self-Service account.']);
                    }
                }
                if (filled($level['alternate_employee_id'] ?? null) && (int) $level['alternate_employee_id'] === (int) $level['approver_employee_id']) {
                    throw ValidationException::withMessages(['levels' => 'The alternate must be a different employee.']);
                }
            }
            $key = implode(':', array_map(fn ($scope) => $attributes[$scope.'_id'] ?: 0, ['employee', 'branch', 'department', 'designation']));
            if ($attributes['is_active'] && LeaveApprovalWorkflow::where('active_scope_key', $key)->when($workflow->exists, fn ($q) => $q->whereKeyNot($workflow->id))->exists()) {
                throw ValidationException::withMessages(['name' => 'An active workflow already uses this exact scope. Edit or disable that workflow first.']);
            }
            $attributes['active_scope_key'] = $attributes['is_active'] ? $key : null;
            $attributes['version'] = $workflow->exists ? $workflow->version + 1 : 1;
            $workflow->fill($attributes)->save();
            $workflow->levels()->delete();
            foreach (array_values($data['levels']) as $index => $level) {
                $workflow->levels()->create(Arr::only($level, ['label', 'approver_employee_id', 'alternate_employee_id']) + ['sequence' => $index + 1]);
            }
            DB::table('leave_workflow_revisions')->insert([
                'workflow_id' => $workflow->id, 'version' => $workflow->version, 'actor_id' => $actor->id,
                'configuration' => json_encode($attributes + ['levels' => array_values($data['levels'])]),
                'created_at' => now(), 'updated_at' => now(),
            ]);

            return $workflow;
        });
    }
}
