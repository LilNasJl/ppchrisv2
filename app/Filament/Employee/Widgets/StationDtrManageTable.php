<?php

namespace App\Filament\Employee\Widgets;

use App\Filament\Widgets\DtrManageTable;
use App\Models\Dtr;
use App\Models\DtrChangeRequest;
use App\Models\EmployeeVisibleDtr;
use App\Services\DtrChangeRequestService;
use App\Services\StationManagementAccess;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StationDtrManageTable extends DtrManageTable
{
    public static function canView(): bool
    {
        return StationManagementAccess::canAccessStationManagement(auth()->user());
    }

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->query(fn (): Builder => $this->getDtrQuery())
            ->headerActions([
                ActionGroup::make([
                    Action::make('dtrOverview')
                        ->label('D.T.R Overview')
                        ->icon('heroicon-m-chart-bar')
                        ->modalHeading('D.T.R Overview')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->modalWidth('3xl')
                        ->modalContent(fn () => view('filament.widgets.dtr-overview-modal', [
                            'overview' => $this->getDtrOverview(),
                        ])),

                    Action::make('viewComments')
                        ->label('View Comments')
                        ->icon('heroicon-m-chat-bubble-left-right')
                        ->modalHeading('D.T.R Comments')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->modalWidth('4xl')
                        ->modalContent(fn () => view('filament.widgets.dtr-comments-modal', [
                            'comments' => $this->getDtrComments(),
                        ])),

                    Action::make('printDtr')
                        ->label('Print / PDF D.T.R')
                        ->icon('heroicon-m-printer')
                        ->url(fn (): string => route('sicrc_tools.dtr.print', [
                            'period' => $this->getSelectedPayrollPeriod()?->publicKey(),
                            'branch' => $this->getBranch()?->publicKey(),
                            'employee' => $this->getEmployee()?->publicKey(),
                        ]))
                        ->openUrlInNewTab()
                        ->disabled(fn (): bool => ! $this->getSelectedPayrollPeriod() || ! $this->getBranch() || ! $this->getEmployee()),
                ])
                    ->label('D.T.R Actions')
                    ->icon('heroicon-m-chevron-down')
                    ->button(),
            ])
            ->description('Synchronized station D.T.R records managed by authorized station managers. Approved attendance and overtime updates are automatically reflected in the HR Panel.');
    }

    protected function getDtrQuery(): Builder
    {
        if (blank($this->getSelectedPayrollPeriodId()) || blank($this->getBranchId()) || blank($this->getFingerprintId())) {
            return EmployeeVisibleDtr::query()->whereRaw('1 = 0');
        }

        return $this->getScopedDtrQuery()
            ->with(['payrollPeriod', 'holiday'])
            ->latest('date_in')
            ->latest('time_in');
    }

    protected function getScopedDtrQuery(): Builder
    {
        $employee = $this->getEmployee();

        if (! $employee) {
            return EmployeeVisibleDtr::query()->whereRaw('1 = 0');
        }

        return EmployeeVisibleDtr::query()
            ->where('payroll_period_id', $this->getSelectedPayrollPeriodId())
            ->forEmployee($employee);
    }

    public function getPendingChangeRequestForRecord(Dtr $record): ?DtrChangeRequest
    {
        $dtrDate = $record->date_in ? Carbon::parse($record->date_in)->toDateString() : null;

        return DtrChangeRequest::query()
            ->where('employee_id', $record->employee_id)
            ->where('status', DtrChangeRequest::STATUS_PENDING)
            ->where(function (Builder $query) use ($record, $dtrDate): void {
                if ($record instanceof EmployeeVisibleDtr) {
                    $query->where('employee_visible_dtr_id', $record->id);
                } else {
                    $query->where('dtr_id', $record->id);
                }

                if ($dtrDate) {
                    $query->orWhere(function (Builder $sub) use ($record, $dtrDate): void {
                        $sub->where('payroll_period_id', $record->payroll_period_id)
                            ->whereDate('dtr_date', $dtrDate);
                    });
                }
            })
            ->latest('id')
            ->first();
    }

    protected function canEditDtrRecord(Dtr $record): bool
    {
        if ($record->isControlledOnFieldDtr()) {
            return false;
        }

        if ($this->isRecordLocked($record)) {
            return false;
        }

        $changeRequest = $this->getPendingChangeRequestForRecord($record);

        if (! $changeRequest) {
            return false;
        }

        if ($changeRequest->status !== DtrChangeRequest::STATUS_PENDING) {
            return false;
        }

        if (! $changeRequest->is_first_level) {
            return false;
        }

        if (! $changeRequest->is_editable_type) {
            return false;
        }

        return $changeRequest->canBeEditedBy(auth()->user());
    }

    protected function canDeleteDtrRecord(Dtr $record): bool
    {
        return false;
    }

    protected function updateDtr(Dtr $record, array $data): bool
    {
        if (! $this->canEditDtrRecord($record)) {
            Notification::make()
                ->title('Editing not permitted')
                ->body('DTR entries cannot be edited without an active first-level Change Request.')
                ->danger()
                ->send();

            return false;
        }

        $changeRequest = $this->getPendingChangeRequestForRecord($record);

        if (! parent::updateDtr($record, $data)) {
            return false;
        }

        if ($changeRequest && auth()->user()?->employee) {
            app(DtrChangeRequestService::class)->saveCorrections(
                $changeRequest,
                auth()->user()->employee,
                [
                    'date_in' => $record->date_in,
                    'time_in' => $record->time_in,
                    'date_out' => $record->date_out,
                    'time_out' => $record->time_out,
                    'schedule_type' => $record->schedule_type,
                    'schedule_start' => $record->schedule_start,
                    'schedule_end' => $record->schedule_end,
                    'comment' => $record->comment,
                ]
            );
        }

        if (! $record instanceof EmployeeVisibleDtr || ! $record->exists) {
            return true;
        }

        $record->forceFill([
            'is_manually_edited' => true,
            'manual_edited_at' => now(),
            'manual_edited_by_employee_id' => auth()->user()?->employee?->id,
            'needs_review' => false,
            'review_reason' => null,
        ])->saveQuietly();

        Notification::make()
            ->title('Station D.T.R preview updated')
            ->body('Corrections have been synchronized to the pending Change Request.')
            ->success()
            ->send();

        return true;
    }
}
