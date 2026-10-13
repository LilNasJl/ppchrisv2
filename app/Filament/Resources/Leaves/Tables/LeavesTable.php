<?php

namespace App\Filament\Resources\Leaves\Tables;

use App\Filament\Resources\Leaves\LeaveResource;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\EmployeeVisibleDtr;
use App\Models\Leave;
use App\Models\LeaveApprovalEvent;
use App\Models\LeaveRequestApproval;
use App\Services\LeaveApprovalAccess;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class LeavesTable
{
    public static function configure(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with(['employee.branch', 'employee.department', 'approvalSteps.approver.user', 'reviewedBy']))
            ->heading('Leave Requests')->defaultSort('created_at', 'desc')->poll('30s')
            ->columns([
                TextColumn::make('index')->label('#')->rowIndex(),
                TextColumn::make('employee.lastname')->label('Employee')->searchable(['firstname', 'lastname', 'middlename'])
                    ->formatStateUsing(fn (Leave $record) => $record->approval_snapshot['employee_name'] ?? $record->employee?->full_name)
                    ->description(fn (Leave $record) => $record->approval_snapshot['branch'] ?? $record->employee?->branch?->branch_name)->weight('semibold'),
                TextColumn::make('leave_type')->label('Leave Type')->searchable(),
                TextColumn::make('pay_treatment')->label('Pay Treatment')->badge()
                    ->formatStateUsing(fn (Leave $record) => $record->isPaid() ? 'Paid' : 'Unpaid')
                    ->color(fn (Leave $record) => $record->isPaid() ? 'success' : 'warning'),
                TextColumn::make('leave_from')->label('From')->date('M d, Y')->sortable(),
                TextColumn::make('leave_to')->label('To')->date('M d, Y'),
                TextColumn::make('days')->state(fn (Leave $record) => $record->getRequestedLeaveDays()),
                TextColumn::make('status')->badge()->formatStateUsing(fn (Leave $record) => $record->approval_label)
                    ->color(fn ($state) => match ($state) {
                        'Pending' => 'warning', 'Approved' => 'success', 'Rejected' => 'danger', default => 'gray'
                    }),
                TextColumn::make('current_approver')->label('Assigned To')->state(fn (Leave $record) => $record->status === 'Pending'
                    ? ($record->approvalSteps->firstWhere('status', 'Pending')?->approver_name ?: 'HR') : '-')
                    ->description(function (Leave $record): ?string {
                        $step = $record->approvalSteps->firstWhere('status', 'Pending');
                        if ($step && ! $step->is_hr && ! LeaveApprovalAccess::employee($step->approver)) {
                            return 'Reassignment required';
                        }

                        return $step?->activated_at?->lt(now()->subHours(48)) ? 'Awaiting review for over 48 hours' : null;
                    })->wrap(),
                TextColumn::make('created_at')->label('Submitted')->dateTime('M d, Y h:i A')->sortable(),
                TextColumn::make('employee.leave_credits')->label('Available Credits')->numeric()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reviewedBy.name')->label('HR Reviewer')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reviewed_at')->label('HR Decision')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View / Review')
                        ->icon(Heroicon::Eye)
                        ->url(fn (Leave $record) => LeaveResource::getUrl('view', ['record' => $record])),

                    Action::make('delete')
                        ->label('Delete')
                        ->visible(fn (Leave $record): bool => $record->status !== 'Approved' && LeaveApprovalAccess::review(auth()->user()))
                        ->icon(Heroicon::Trash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Leave Request')
                        ->modalDescription('Are you sure you want to permanently delete this leave request history? This will return any deducted leave credits, remove associated paid D.T.R records and approval history, and cannot be undone.')
                        ->modalSubmitActionLabel('Permanently Delete')
                        ->action(function (Leave $record): void {
                            DB::transaction(function () use ($record): void {
                                $leave = Leave::withTrashed()->lockForUpdate()->find($record->id);
                                if (! $leave) {
                                    return;
                                }
                                abort_if($leave->status === 'Approved' || ! LeaveApprovalAccess::review(auth()->user()), 403);

                                $employee = Employee::lockForUpdate()->find($leave->employee_id);
                                if ($employee) {
                                    $employee->resetLeaveCreditsIfNeeded();
                                    $employee->refresh();

                                    if ((float) $leave->deducted_leave_credits > 0) {
                                        $employee->increment('leave_credits', (float) $leave->deducted_leave_credits);
                                    }

                                    if ((float) $leave->deducted_birthday_leave_credits > 0) {
                                        $employee->increment('birthday_leave_credits', (float) $leave->deducted_birthday_leave_credits);
                                    }
                                }

                                Dtr::where('leave_id', $leave->id)->forceDelete();
                                EmployeeVisibleDtr::where('leave_id', $leave->id)->delete();

                                $eventIds = LeaveApprovalEvent::where('leave_id', $leave->id)->pluck('id');
                                if ($eventIds->isNotEmpty()) {
                                    DB::table('leave_approval_deliveries')
                                        ->whereIn('event_id', $eventIds)
                                        ->delete();
                                    LeaveApprovalEvent::whereIn('id', $eventIds)->delete();
                                }

                                LeaveRequestApproval::where('leave_id', $leave->id)->delete();

                                $leave->forceDelete();
                            });

                            Notification::make()
                                ->title('Leave request permanently deleted')
                                ->success()
                                ->send();
                        }),
                ])
                    ->icon(Heroicon::Bars3)
                    ->tooltip('Actions'),
            ])->emptyStateHeading('No leave requests in this queue');
    }
}
