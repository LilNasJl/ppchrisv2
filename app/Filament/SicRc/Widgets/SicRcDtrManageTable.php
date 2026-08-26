<?php

namespace App\Filament\SicRc\Widgets;

use App\Filament\Widgets\DtrManageTable;
use App\Models\Dtr;
use App\Models\EmployeeVisibleDtr;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SicRcDtrManageTable extends DtrManageTable
{
    public static function canView(): bool
    {
        return auth('sicrc')->check();
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
            ->description('Employee-visible D.T.R preview records imported by SIC/RC. These records do not affect HR payroll until HR performs the official import/process.');
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

    protected function updateDtr(Dtr $record, array $data): bool
    {
        if (! parent::updateDtr($record, $data)) {
            return false;
        }

        if (! $record instanceof EmployeeVisibleDtr || ! $record->exists) {
            return true;
        }

        $record->forceFill([
            'is_manually_edited' => true,
            'manual_edited_at' => now(),
            'manual_edited_by_sicrc_account_id' => auth('sicrc')->id(),
            'needs_review' => false,
            'review_reason' => null,
        ])->saveQuietly();

        Notification::make()
            ->title('SIC/RC D.T.R preview updated')
            ->success()
            ->send();

        return true;
    }
}
