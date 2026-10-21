<?php

namespace App\Filament\Employee\Pages\Station;

use App\Models\Branch;
use App\Models\DtrChangeRequest;
use App\Models\PayrollPeriod;
use App\Services\StationManagementAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Pages\Page;
use Filament\Resources\Concerns\HasTabs;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StationChangeRequestHistory extends Page implements HasTable
{
    use HasTabs;
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static ?string $slug = 'station/change-requests/history';

    protected static ?string $title = 'Station Change Request History';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Clock;

    public static function canAccess(): bool
    {
        return StationManagementAccess::canAccessStationManagement(auth()->user());
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function mount(): void
    {
        if (! static::canAccess()) {
            throw new HttpException(403, 'Unauthorized access to Station Management.');
        }

        $this->loadDefaultActiveTab();
    }

    public function getBreadcrumbs(): array
    {
        return [
            ReviewDtrChangeRequests::getUrl() => 'Station Change Requests',
            '#' => 'History',
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getTabsContentComponent(),
            EmbeddedTable::make(),
        ]);
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),

            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', DtrChangeRequest::STATUS_APPROVED)),

            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', DtrChangeRequest::STATUS_REJECTED)),

            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', DtrChangeRequest::STATUS_CANCELLED)),

            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', DtrChangeRequest::STATUS_PENDING)),
        ];
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->query(fn (): Builder => $this->modifyQueryWithActiveTab(
                DtrChangeRequest::query()
                    ->forStationHistory($user)
                    ->with(['employee', 'branch', 'payrollPeriod', 'reviewedByEmployee.user', 'assignedEmployee', 'firstLevelEditor.user'])
                    ->latest('updated_at')
            ))
            ->heading('Change Request History')
            ->description('Comprehensive history of attendance change requests, approvals, and decisions.')
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('employee_name_snapshot')
                    ->label('Employee')
                    ->description(fn (DtrChangeRequest $record): string => ($record->employee_company_id_snapshot ? "ID: {$record->employee_company_id_snapshot}" : '') . ($record->branch_name_snapshot ? " • {$record->branch_name_snapshot}" : ''))
                    ->searchable(['employee_name_snapshot', 'employee_company_id_snapshot', 'branch_name_snapshot'])
                    ->sortable(),

                TextColumn::make('dtr_date')
                    ->label('D.T.R Date')
                    ->getStateUsing(function (DtrChangeRequest $record): ?string {
                        if ($record->request_type === DtrChangeRequest::TYPE_MISSING_DTR_ENTRIES) {
                            $count = count($record->proposed_values['entries'] ?? []);
                            $from = $record->date_from ? Carbon::parse($record->date_from)->format('M d') : '';
                            $to = $record->date_to ? Carbon::parse($record->date_to)->format('M d, Y') : '';
                            $range = ($from && $to) ? ($from === Carbon::parse($record->date_to)->format('M d') ? $to : "{$from} - {$to}") : '-';
                            return "{$range} ({$count} entries)";
                        }
                        return filled($record->dtr_date ?: $record->date_from) ? Carbon::parse($record->dtr_date ?: $record->date_from)->format('M d, Y') : '-';
                    })
                    ->description(fn (DtrChangeRequest $record): ?string => $record->created_at?->format('M d, Y h:i A'))
                    ->sortable(),

                TextColumn::make('request_type_label')
                    ->label('Request Type')
                    ->badge()
                    ->icon(fn (DtrChangeRequest $record): string => $record->request_type_icon)
                    ->color(fn (DtrChangeRequest $record): string => $record->request_type_color)
                    ->description(fn (DtrChangeRequest $record): ?string => $record->overtime_summary_description),

                TextColumn::make('display_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (DtrChangeRequest $record): string => match ($record->status) {
                        DtrChangeRequest::STATUS_APPROVED => 'success',
                        DtrChangeRequest::STATUS_REJECTED => 'danger',
                        DtrChangeRequest::STATUS_CANCELLED => 'gray',
                        default => 'warning',
                    })
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('status', $direction)),

                TextColumn::make('decision_summary')
                    ->label('Decision / Approver')
                    ->getStateUsing(function (DtrChangeRequest $record): string {
                        if ($record->status === DtrChangeRequest::STATUS_APPROVED) {
                            return 'Approved by ' . ($record->reviewer_name ?? 'Approver');
                        }
                        if ($record->status === DtrChangeRequest::STATUS_REJECTED) {
                            return 'Rejected by ' . ($record->reviewer_name ?? 'Approver');
                        }
                        if ($record->status === DtrChangeRequest::STATUS_CANCELLED) {
                            return 'Cancelled by Employee';
                        }
                        return $record->current_step_label ? "Pending ({$record->current_step_label})" : 'Pending';
                    })
                    ->description(fn (DtrChangeRequest $record): ?string => $record->reviewed_at?->format('M d, Y h:i A') ?? ($record->cancelled_at?->format('M d, Y h:i A') ?? null)),

                TextColumn::make('branch_name_snapshot')
                    ->label('Station / Branch')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('payroll_period_title_snapshot')
                    ->label('Payroll Period')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Date Submitted')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('description')
                    ->label('Explanation')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('request_type')
                    ->label('Request Type')
                    ->options(DtrChangeRequest::requestTypeOptions()),

                SelectFilter::make('branch_id')
                    ->label('Station / Branch')
                    ->options(function () {
                        $user = auth()->user();
                        $branches = StationManagementAccess::getAssignedBranches($user);
                        if (! empty($branches)) {
                            return collect($branches)->pluck('branch_name', 'branch_id')->all();
                        }
                        return Branch::query()->orderBy('branch_name')->pluck('branch_name', 'id')->all();
                    })
                    ->searchable(),

                SelectFilter::make('payroll_period_id')
                    ->label('Payroll Period')
                    ->relationship('payrollPeriod', 'title')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View Details')
                        ->icon(Heroicon::Eye)
                        ->modalHeading('D.T.R Change Request Details')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->modalWidth('2xl')
                        ->modalContent(fn (DtrChangeRequest $record) => view('filament.employee.pages.partials.dtr-change-request-details-modal', [
                            'record' => $record,
                        ])),
                ])
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->tooltip('Actions'),
            ])
            ->striped()
            ->defaultPaginationPageOption(10);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to Change Requests')
                ->icon(Heroicon::ArrowLeft)
                ->color('gray')
                ->url(fn (): string => ReviewDtrChangeRequests::getUrl()),
        ];
    }
}
