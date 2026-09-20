<?php

namespace App\Filament\Support;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\PayrollPeriod;
use App\Services\LeaveApprovalAccess;
use App\Services\LeaveApprovalService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class LeaveReviewActions
{
    public static function make(Leave $leave, bool $hr): array
    {
        $canAct = fn (): bool => $leave->fresh()->status === 'Pending' && ($hr
            ? LeaveApprovalAccess::review(auth()->user()) && $leave->fresh()->isReadyForHr()
                && (int) auth()->user()->employee?->id !== (int) $leave->employee_id
            : $leave->approvalSteps()->where('status', 'Pending')->where('is_hr', false)
                ->where('approver_employee_id', auth()->user()->employee?->id)->exists());
        $stepField = fn () => Hidden::make('step_id')->default(fn () => $leave->approvalSteps()->where('status', 'Pending')->value('id'));
        $approve = Action::make('approveLeave')->label($hr ? 'Final Approval' : 'Approve')
            ->icon(Heroicon::CheckCircle)->color('success')->visible($canAct)
            ->modalHeading($hr ? 'Approve leave and create paid DTR' : 'Approve this level')
            ->modalSubmitActionLabel($hr ? 'Confirm Final Approval' : 'Confirm Approval')
            ->schema(array_filter([
                $stepField(),
                $hr ? Select::make('payroll_period_id')->label('Payroll Period')->required()->searchable()
                    ->options(fn () => PayrollPeriod::where('is_locked', false)
                        ->whereDate('date_start', '<=', $leave->leave_from)->whereDate('date_end', '>=', $leave->leave_to)
                        ->orderByDesc('date_start')->pluck('title', 'id')->all()) : null,
                Textarea::make('remarks')->label('Remarks')->maxLength(2000)->rows(3),
            ]))->action(function (array $data) use ($leave, $hr): void {
                self::run(function () use ($leave, $hr, $data): void {
                    $service = app(LeaveApprovalService::class);
                    if ($hr) {
                        $service->decideHr($leave, auth()->user(), true, PayrollPeriod::findOrFail($data['payroll_period_id']), $data['remarks'] ?? null);
                    } else {
                        $service->decide($leave, (int) $data['step_id'], auth()->user(), true, $data['remarks'] ?? null);
                    }
                }, 'Leave approval recorded');
                $leave->refresh();
            });
        $reject = Action::make('rejectLeave')->label('Reject')->icon(Heroicon::XCircle)->color('danger')
            ->outlined()->visible($canAct)->modalHeading('Reject leave request')->modalSubmitActionLabel('Confirm Rejection')
            ->schema([$stepField(), Textarea::make('remarks')->label('Reason for Rejection')->required()->maxLength(2000)->rows(3)])
            ->action(function (array $data) use ($leave, $hr): void {
                self::run(function () use ($leave, $hr, $data): void {
                    if ($hr) {
                        app(LeaveApprovalService::class)->decideHr($leave, auth()->user(), false, null, $data['remarks']);
                    } else {
                        app(LeaveApprovalService::class)->decide($leave, (int) $data['step_id'], auth()->user(), false, $data['remarks']);
                    }
                }, 'Leave request rejected');
                $leave->refresh();
            });
        $actions = [$approve, $reject];
        if ($hr) {
            $actions[] = ActionGroup::make([
                Action::make('reassignApprover')->label('Reassign Approver')->icon(Heroicon::UserPlus)
                    ->schema([$stepField(), Select::make('employee_id')->label('Replacement Approver')->required()->searchable()
                        ->options(fn () => self::employeeOptions((int) $leave->employee_id)),
                        Textarea::make('reason')->required()->maxLength(2000)])
                    ->modalSubmitActionLabel('Confirm Reassignment')
                    ->action(function (array $data) use ($leave): void {
                        self::run(fn () => app(LeaveApprovalService::class)->reassign($leave, (int) $data['step_id'], Employee::findOrFail($data['employee_id']), auth()->user(), $data['reason']), 'Approver reassigned');
                        $leave->refresh();
                    }),
                Action::make('skipLevel')->label('Skip Current Level')->icon(Heroicon::Forward)->color('warning')
                    ->modalHeading('Skip preliminary approval level')->modalSubmitActionLabel('Confirm Skip')
                    ->schema([$stepField(), Textarea::make('reason')->label('Override Reason')->required()->maxLength(2000)])
                    ->action(function (array $data) use ($leave): void {
                        self::run(fn () => app(LeaveApprovalService::class)->skip($leave, (int) $data['step_id'], auth()->user(), $data['reason']), 'Approval level skipped');
                        $leave->refresh();
                    }),
            ])->label('Administration')->icon(Heroicon::EllipsisHorizontal)->button()->color('gray')
                ->visible(fn () => LeaveApprovalAccess::override(auth()->user()) && $leave->fresh()->status === 'Pending' && $leave->fresh()->approval_phase === 'preliminary');
        }

        return $actions;
    }

    public static function employeeOptions(?int $exclude = null): array
    {
        return Employee::activeEmployment()->whereHas('user', fn ($q) => $q->where('role', 'employee')->where('is_disabled', false))
            ->when($exclude, fn ($q) => $q->whereKeyNot($exclude))->orderBy('lastname')->orderBy('firstname')->get()
            ->mapWithKeys(fn ($employee) => [$employee->id => $employee->full_name.' ('.$employee->company_id.')'])->all();
    }

    public static function run(callable $callback, string $success): void
    {
        try {
            $callback();
            Notification::make()->title($success)->success()->send();
        } catch (\RuntimeException $exception) {
            Notification::make()->title('Unable to process request')->body($exception->getMessage())->danger()->send();
        }
    }
}
