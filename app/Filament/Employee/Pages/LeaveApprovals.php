<?php

namespace App\Filament\Employee\Pages;

use App\Models\Leave;
use App\Models\LeaveRequestApproval;
use App\Services\LeaveApprovalAccess;
use App\Services\StationManagementAccess;
use BackedEnum;
use Filament\Actions\Action;
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

class LeaveApprovals extends Page implements HasTable
{
    use HasTabs;
    use InteractsWithTable;

    protected string $view = 'filament.employee.pages.table-page';
    protected static ?string $title = 'Leave Approvals';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentCheck;
    protected static string|\UnitEnum|null $navigationGroup = 'Station Management';
    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return LeaveApprovalAccess::inbox(auth()->user());
    }

    public static function getNavigationBadge(): ?string
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('leave_request_approvals') || ! \Illuminate\Support\Facades\Schema::hasTable('leaves')) {
            return null;
        }

        $user = auth()->user();
        if (! StationManagementAccess::canAccessStationManagement($user)) {
            return null;
        }

        $count = LeaveRequestApproval::where('approver_employee_id', $user?->employee?->id)
            ->where('status', 'Pending')
            ->whereHas('leave', fn (Builder $q) => $q->where('status', 'Pending')
                ->whereHas('employee', fn (Builder $empQ) => StationManagementAccess::scopeManagedEmployeesQuery($empQ, $user)))
            ->count();

        return $count ? (string) $count : null;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->loadDefaultActiveTab();
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function getTabs(): array
    {
        $tabs = [];
        foreach (['Pending', 'Approved', 'Rejected'] as $status) {
            $tabs[$status] = Tab::make($status)->modifyQueryUsing(fn (Builder $query) => $query->whereHas('approvalSteps', fn ($q) => $q
                ->where('approver_employee_id', auth()->user()->employee->id)->where('status', $status)));
        }
        $tabs['History'] = Tab::make('Approval History');

        return $tabs;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([$this->getTabsContentComponent(), EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        return $table->query(fn () => $this->modifyQueryWithActiveTab(Leave::with(['employee.branch', 'employee.department', 'approvalSteps'])
            ->whereHas('employee', fn (Builder $empQ) => StationManagementAccess::scopeManagedEmployeesQuery($empQ, auth()->user()))
            ->whereHas('approvalSteps', fn ($q) => $q->where('approver_employee_id', auth()->user()->employee->id)->whereNotNull('activated_at'))))
            ->defaultSort('created_at', 'desc')->poll('30s')
            ->columns([
                TextColumn::make('employee.lastname')->label('Employee')->formatStateUsing(fn (Leave $record) => $record->approval_snapshot['employee_name'] ?? $record->employee?->full_name)
                    ->description(fn (Leave $record) => $record->approval_snapshot['branch'] ?? $record->employee?->branch?->branch_name)
                    ->searchable(['firstname', 'lastname']),
                TextColumn::make('branch_scope')
                    ->label('Station / Scope')
                    ->state(fn (Leave $record): string => (string) ($record->approval_snapshot['branch'] ?? $record->employee?->branch?->branch_name ?? '-'))
                    ->description(fn (Leave $record): ?string => $record->approval_snapshot['department'] ?? $record->employee?->department?->name),
                TextColumn::make('leave_type')->label('Leave Type'),
                TextColumn::make('leave_from')->label('From')->date('M d, Y'),
                TextColumn::make('leave_to')->label('To')->date('M d, Y'),
                TextColumn::make('days')->state(fn (Leave $record) => $record->getRequestedLeaveDays()),
                TextColumn::make('current_approval_order')
                    ->label('Approval Level')
                    ->state(function (Leave $record): string {
                        $step = $record->approvalSteps->firstWhere('sequence', $record->current_approval_order)
                            ?? $record->approvalSteps->firstWhere('approver_employee_id', auth()->user()?->employee?->id);
                        return $step ? "L{$step->sequence}: {$step->label}" : $record->approval_label;
                    })
                    ->badge()
                    ->color('info'),
                TextColumn::make('status')->formatStateUsing(fn (Leave $record) => $record->approval_label)->badge()
                    ->color(fn ($state) => match ($state) { 'Approved' => 'success', 'Rejected' => 'danger', 'Pending' => 'warning', default => 'gray' }),
                TextColumn::make('created_at')->label('Submitted')->since()->tooltip(fn (Leave $record) => $record->created_at->format('M d, Y h:i A')),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Station / Scope')
                    ->options(function (): array {
                        $branches = StationManagementAccess::getAssignedBranches(auth()->user());
                        return collect($branches)->pluck('branch_name', 'branch_id')->all();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        $branchId = $data['value'] ?? null;
                        if (blank($branchId)) {
                            return $query;
                        }
                        return $query->whereHas('employee', fn (Builder $q): Builder => $q->where('branch_id', (int) $branchId));
                    }),
            ])
            ->recordActions([
                Action::make('review')->label('View / Review')->icon(Heroicon::Eye)->button()
                    ->url(fn (Leave $record) => ViewLeaveApproval::getUrl(['leaveId' => $record->publicKey()])),
            ])->emptyStateHeading('No leave requests in this queue');
    }
}
