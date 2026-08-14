<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Services\DtrRecordService;
use App\Services\OvertimeApprovalService;
use App\Services\PayrollCalculator;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

class OvertimeManagement extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Overtime Approval';

    public ?int $employeeId = null;

    public ?int $periodId = null;

    public ?int $branchId = null;

    public ?string $returnSearch = null;

    public int $returnPage = 1;

    public int $returnPerPage = 10;

    public string $returnPreset = 'summary';

    public static function canAccess(): bool
    {
        return PayrollByBranch::canAccess();
    }

    public function mount(): void
    {
        $this->employeeId = Employee::resolvePublicId(request()->query('employeeId'));
        $this->periodId = PayrollPeriod::resolvePublicId(request()->query('periodId'));
        $this->branchId = Branch::resolvePublicId(request()->query('branchId'));
        $this->returnSearch = str(request()->query('returnSearch', ''))->limit(120)->toString();
        $this->returnPage = max(1, request()->integer('returnPage', 1));
        $requestedPerPage = request()->integer('returnPerPage', 10);
        $this->returnPerPage = in_array($requestedPerPage, [10, 25, 50, 100], true)
            ? $requestedPerPage
            : 10;
        $requestedPreset = (string) request()->query('returnPreset', 'summary');
        $this->returnPreset = in_array($requestedPreset, ['summary', 'earnings', 'deductions', 'remittances', 'all'], true)
            ? $requestedPreset
            : 'summary';

        $employee = $this->employee;
        $period = $this->period;

        abort_unless(
            $employee
                && $period
                && $this->branch
                && (int) $employee->branch_id === (int) $this->branchId
                && ! app(PayrollCalculator::class)->isEmployeePayrollExcluded($period, $employee),
            404,
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(fn (): string => ($this->employee?->full_name ?? 'Employee').' - Overtime')
            ->description(fn (): string => 'Payroll period: '.($this->period?->title ?? '-'))
            ->query(fn (): Builder => $this->dtrQuery())
            ->defaultSort('date_in')
            ->striped()
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('date_in')
                    ->label('Date In')
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('time_in')
                    ->label('In')
                    ->placeholder('-'),

                TextColumn::make('date_out')
                    ->label('Date Out')
                    ->date('M d, Y')
                    ->placeholder('-'),

                TextColumn::make('time_out')
                    ->label('Out')
                    ->placeholder('-'),

                TextColumn::make('schedule_type')
                    ->label('Schedule')
                    ->badge()
                    ->placeholder('-'),

                TextColumn::make('overtime')
                    ->label('After OT')
                    ->numeric()
                    ->alignEnd(),

                TextColumn::make('early_clock_in')
                    ->label('Early OT')
                    ->numeric()
                    ->alignEnd(),

                TextColumn::make('credited_early_clock_in')
                    ->label('Cred. Early')
                    ->numeric()
                    ->alignEnd(),

                TextColumn::make('credited_overtime')
                    ->label('Total Cred. OT')
                    ->numeric()
                    ->alignEnd(),

                TextColumn::make('overtime_status')
                    ->label('OT Status')
                    ->badge()
                    ->color(fn (?string $state): string => match (strtolower((string) $state)) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actionsColumnLabel('OT Actions')
            ->recordActions([
                Action::make('approveOvertime')
                    ->label('Approve')
                    ->icon(Heroicon::CheckCircle)
                    ->color('success')
                    ->modalHeading('Approve overtime')
                    ->modalDescription(fn (Dtr $record): string => sprintf(
                        'Calculated early overtime: %d minutes. Calculated after-schedule overtime: %d minutes. Enter the credited minutes for each eligible type.',
                        (int) $record->early_clock_in,
                        (int) $record->overtime,
                    ))
                    ->schema([
                        TextInput::make('credited_early_minutes')
                            ->label('Credited Early Overtime (Minutes)')
                            ->numeric()
                            ->integer()
                            ->required(fn (Dtr $record): bool => app(OvertimeApprovalService::class)
                                ->hasEligibleEarlyOvertime($record))
                            ->visible(fn (Dtr $record): bool => app(OvertimeApprovalService::class)
                                ->hasEligibleEarlyOvertime($record))
                            ->minValue(0)
                            ->maxValue(fn (Dtr $record): int => (int) $record->early_clock_in)
                            ->default(fn (Dtr $record): int => app(OvertimeApprovalService::class)
                                ->defaultCreditedEarlyOvertime($record))
                            ->helperText(fn (Dtr $record): string => sprintf(
                                'Enter 0 for no credit, or a whole number from 30 to %d minutes.',
                                (int) $record->early_clock_in,
                            )),

                        TextInput::make('credited_overtime_minutes')
                            ->label('Credited After-Schedule Overtime (Minutes)')
                            ->numeric()
                            ->integer()
                            ->required(fn (Dtr $record): bool => app(OvertimeApprovalService::class)
                                ->hasEligibleOvertime($record))
                            ->visible(fn (Dtr $record): bool => app(OvertimeApprovalService::class)
                                ->hasEligibleOvertime($record))
                            ->minValue(0)
                            ->maxValue(fn (Dtr $record): int => (int) $record->overtime)
                            ->default(fn (Dtr $record): int => app(OvertimeApprovalService::class)
                                ->defaultCreditedOvertime($record))
                            ->helperText(fn (Dtr $record): string => sprintf(
                                'Enter 0 for no credit, or a whole number from 30 to %d minutes.',
                                (int) $record->overtime,
                            )),
                    ])
                    ->modalSubmitActionLabel('Approve Overtime')
                    ->visible(fn (Dtr $record): bool => ! $this->isLocked()
                        && app(OvertimeApprovalService::class)->isPending($record))
                    ->action(fn (Dtr $record, array $data): mixed => $this->approveOvertime(
                        $record,
                        isset($data['credited_overtime_minutes'])
                            ? (int) $data['credited_overtime_minutes']
                            : null,
                        isset($data['credited_early_minutes'])
                            ? (int) $data['credited_early_minutes']
                            : null,
                    )),

                Action::make('rejectOvertime')
                    ->label('Reject')
                    ->icon(Heroicon::XCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject overtime?')
                    ->modalDescription(fn (Dtr $record): string => sprintf(
                        'The eligible early overtime (%d minutes) and after-schedule overtime (%d minutes) on this D.T.R entry will not be credited.',
                        (int) $record->early_clock_in,
                        (int) $record->overtime,
                    ))
                    ->modalSubmitActionLabel('Reject Overtime')
                    ->visible(fn (Dtr $record): bool => ! $this->isLocked()
                        && app(OvertimeApprovalService::class)->isPending($record))
                    ->action(fn (Dtr $record): mixed => $this->rejectOvertime($record)),

                ActionGroup::make([
                    Action::make('undoApproval')
                        ->label('Undo Approval')
                        ->icon(Heroicon::ArrowPath)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Undo overtime approval?')
                        ->modalDescription('The overtime minutes will be removed from credited overtime and credited work hours.')
                        ->visible(fn (Dtr $record): bool => ! $this->isLocked()
                            && app(OvertimeApprovalService::class)->isApproved($record))
                        ->action(fn (Dtr $record): mixed => $this->undoOvertime($record)),

                    Action::make('reopenRejected')
                        ->label('Reopen Rejected OT')
                        ->icon(Heroicon::ArrowPath)
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalHeading('Return overtime to pending?')
                        ->visible(fn (Dtr $record): bool => ! $this->isLocked()
                            && app(OvertimeApprovalService::class)->isRejected($record))
                        ->action(fn (Dtr $record): mixed => $this->undoOvertime($record)),
                ])
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->tooltip('Actions'),
            ])
            ->emptyStateHeading('No D.T.R entries are available for this employee.');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function getEmployeeProperty(): ?Employee
    {
        return filled($this->employeeId) ? Employee::query()->find($this->employeeId) : null;
    }

    public function getPeriodProperty(): ?PayrollPeriod
    {
        return filled($this->periodId) ? PayrollPeriod::query()->find($this->periodId) : null;
    }

    public function getBranchProperty(): ?Branch
    {
        return filled($this->branchId) ? Branch::query()->find($this->branchId) : null;
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(fn (): string => $this->returnUrl()),
        ];
    }

    protected function dtrQuery(): Builder
    {
        $employee = $this->employee;

        if (! $employee || blank($this->branchId) || blank($this->periodId)) {
            return Dtr::query()->whereRaw('1 = 0');
        }

        return app(DtrRecordService::class)
            ->query($employee, $this->branchId, $this->periodId)
            ->with('payrollPeriod')
            ->orderBy('date_in')
            ->orderBy('time_in')
            ->orderBy('id');
    }

    protected function approveOvertime(
        Dtr $record,
        ?int $creditedOvertimeMinutes,
        ?int $creditedEarlyMinutes,
    ): void {
        $this->runTransition(
            fn (): int => app(OvertimeApprovalService::class)->approve(
                $record,
                $creditedOvertimeMinutes,
                $creditedEarlyMinutes,
            ),
            'Overtime approved',
        );
    }

    protected function rejectOvertime(Dtr $record): void
    {
        $this->runTransition(
            fn (): int => app(OvertimeApprovalService::class)->reject($record),
            'Overtime rejected',
        );
    }

    protected function undoOvertime(Dtr $record): void
    {
        $this->runTransition(
            fn (): int => app(OvertimeApprovalService::class)->undo($record),
            'Overtime returned to pending',
        );
    }

    protected function runTransition(callable $transition, string $successTitle): void
    {
        try {
            $count = $transition();

            Notification::make()
                ->title($successTitle)
                ->body("{$count} D.T.R overtime record(s) updated.")
                ->success()
                ->send();

            $this->resetTable();
        } catch (\DomainException $exception) {
            Notification::make()
                ->title('Overtime was not updated')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function isLocked(): bool
    {
        return (bool) $this->period?->is_locked;
    }

    protected function returnUrl(): string
    {
        return PayrollByBranch::getUrl([
            'periodId' => $this->period?->publicKey(),
            'branchId' => $this->branch?->publicKey(),
            'payrollSearch' => $this->returnSearch,
            'payrollPage' => $this->returnPage,
            'payrollPerPage' => $this->returnPerPage,
            'payrollPreset' => $this->returnPreset,
        ]);
    }
}
