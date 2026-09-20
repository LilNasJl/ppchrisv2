<?php

namespace App\Filament\Resources\Leaves\Tables;

use App\Filament\Resources\Leaves\LeaveResource;
use App\Models\Leave;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LeavesTable
{
    public static function configure(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with(['employee.branch', 'employee.department', 'approvalSteps.approver.user', 'reviewedBy']))
            ->defaultSort('created_at', 'desc')->poll('30s')
            ->columns([
                TextColumn::make('index')->label('#')->rowIndex(),
                TextColumn::make('employee.lastname')->label('Employee')->searchable(['firstname', 'lastname', 'middlename'])
                    ->formatStateUsing(fn (Leave $record) => $record->approval_snapshot['employee_name'] ?? $record->employee?->full_name)
                    ->description(fn (Leave $record) => $record->approval_snapshot['branch'] ?? $record->employee?->branch?->branch_name)->weight('semibold'),
                TextColumn::make('leave_type')->label('Leave Type')->searchable(),
                TextColumn::make('leave_from')->label('From')->date('M d, Y')->sortable(),
                TextColumn::make('leave_to')->label('To')->date('M d, Y'),
                TextColumn::make('days')->state(fn (Leave $record) => $record->getRequestedLeaveDays()),
                TextColumn::make('status')->badge()->formatStateUsing(fn (Leave $record) => $record->approval_label)
                    ->color(fn ($state) => match ($state) { 'Pending' => 'warning', 'Approved' => 'success', 'Rejected' => 'danger', default => 'gray' }),
                TextColumn::make('current_approver')->label('Assigned To')->state(fn (Leave $record) => $record->status === 'Pending'
                    ? ($record->approvalSteps->firstWhere('status', 'Pending')?->approver_name ?: 'HR') : '-')
                    ->description(function (Leave $record): ?string {
                        $step = $record->approvalSteps->firstWhere('status', 'Pending');
                        if ($step && ! $step->is_hr && ! \App\Services\LeaveApprovalAccess::employee($step->approver)) {
                            return 'Reassignment required';
                        }

                        return $step?->activated_at?->lt(now()->subHours(48)) ? 'Awaiting review for over 48 hours' : null;
                    })->wrap(),
                TextColumn::make('created_at')->label('Submitted')->dateTime('M d, Y h:i A')->sortable(),
                TextColumn::make('employee.leave_credits')->label('Available Credits')->numeric()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reviewedBy.name')->label('HR Reviewer')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reviewed_at')->label('HR Decision')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])->recordActions([
                Action::make('view')->label('View / Review')->icon(Heroicon::Eye)->button()
                    ->url(fn (Leave $record) => LeaveResource::getUrl('view', ['record' => $record])),
            ])->emptyStateHeading('No leave requests in this queue');
    }
}
