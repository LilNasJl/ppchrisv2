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

    protected string $view = 'filament.employee.pages.station.leave-approvals';
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

    public function getSubheading(): string
    {
        return 'Review and decide on leave requests submitted by employees within your assigned stations.';
    }

    public function getLeaveApprovalStats(): array
    {
        $employee = auth()->user()?->employee;
        if (! $employee) {
            return [
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
                'total' => 0,
            ];
        }

        $baseQuery = fn () => Leave::query()
            ->whereHas('employee', fn (Builder $empQ) => StationManagementAccess::scopeManagedEmployeesQuery($empQ, auth()->user()))
            ->whereHas('approvalSteps', fn ($q) => $q->where('approver_employee_id', $employee->id)->whereNotNull('activated_at'));

        $pending = (clone $baseQuery())->whereHas('approvalSteps', fn ($q) => $q->where('approver_employee_id', $employee->id)->where('status', 'Pending'))->count();
        $approved = (clone $baseQuery())->whereHas('approvalSteps', fn ($q) => $q->where('approver_employee_id', $employee->id)->where('status', 'Approved'))->count();
        $rejected = (clone $baseQuery())->whereHas('approvalSteps', fn ($q) => $q->where('approver_employee_id', $employee->id)->where('status', 'Rejected'))->count();
        $total = $baseQuery()->count();

        return [
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'total' => $total,
        ];
    }

    public function getTabs(): array
    {
        $employee = auth()->user()?->employee;
        $employeeId = $employee?->id;

        return [
            'Pending' => Tab::make('Pending')
                ->icon(Heroicon::Clock)
                ->badge(fn () => Leave::query()
                    ->whereHas('employee', fn (Builder $empQ) => StationManagementAccess::scopeManagedEmployeesQuery($empQ, auth()->user()))
                    ->whereHas('approvalSteps', fn ($q) => $q->where('approver_employee_id', $employeeId)->whereNotNull('activated_at')->where('status', 'Pending'))
                    ->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('approvalSteps', fn ($q) => $q
                    ->where('approver_employee_id', $employeeId)->where('status', 'Pending'))),

            'Approved' => Tab::make('Approved')
                ->icon(Heroicon::CheckCircle)
                ->badge(fn () => Leave::query()
                    ->whereHas('employee', fn (Builder $empQ) => StationManagementAccess::scopeManagedEmployeesQuery($empQ, auth()->user()))
                    ->whereHas('approvalSteps', fn ($q) => $q->where('approver_employee_id', $employeeId)->whereNotNull('activated_at')->where('status', 'Approved'))
                    ->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('approvalSteps', fn ($q) => $q
                    ->where('approver_employee_id', $employeeId)->where('status', 'Approved'))),

            'Rejected' => Tab::make('Rejected')
                ->icon(Heroicon::XCircle)
                ->badge(fn () => Leave::query()
                    ->whereHas('employee', fn (Builder $empQ) => StationManagementAccess::scopeManagedEmployeesQuery($empQ, auth()->user()))
                    ->whereHas('approvalSteps', fn ($q) => $q->where('approver_employee_id', $employeeId)->whereNotNull('activated_at')->where('status', 'Rejected'))
                    ->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('approvalSteps', fn ($q) => $q
                    ->where('approver_employee_id', $employeeId)->where('status', 'Rejected'))),

            'History' => Tab::make('All Records')
                ->icon(Heroicon::ListBullet)
                ->badge(fn () => Leave::query()
                    ->whereHas('employee', fn (Builder $empQ) => StationManagementAccess::scopeManagedEmployeesQuery($empQ, auth()->user()))
                    ->whereHas('approvalSteps', fn ($q) => $q->where('approver_employee_id', $employeeId)->whereNotNull('activated_at'))
                    ->count())
                ->badgeColor('gray'),
        ];
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
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('employee.lastname')
                    ->label('Employee')
                    ->formatStateUsing(fn (Leave $record) => $record->approval_snapshot['employee_name'] ?? $record->employee?->full_name)
                    ->description(fn (Leave $record) => $record->approval_snapshot['branch'] ?? $record->employee?->branch?->branch_name)
                    ->weight('bold')
                    ->searchable(['firstname', 'lastname']),

                TextColumn::make('branch_scope')
                    ->label('Station / Scope')
                    ->icon('heroicon-m-building-storefront')
                    ->state(fn (Leave $record): string => (string) ($record->approval_snapshot['branch'] ?? $record->employee?->branch?->branch_name ?? '-'))
                    ->description(fn (Leave $record): ?string => $record->approval_snapshot['department'] ?? $record->employee?->department?->name),

                TextColumn::make('leave_type')
                    ->label('Leave Type')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains(strtolower($state), 'vacation') => 'info',
                        str_contains(strtolower($state), 'sick') => 'warning',
                        str_contains(strtolower($state), 'birthday') => 'success',
                        str_contains(strtolower($state), 'half') => 'gray',
                        default => 'purple',
                    })
                    ->icon('heroicon-m-tag')
                    ->weight('semibold'),

                TextColumn::make('leave_from')
                    ->label('From')
                    ->icon('heroicon-m-calendar-days')
                    ->date('M d, Y'),

                TextColumn::make('leave_to')
                    ->label('To')
                    ->icon('heroicon-m-calendar-days')
                    ->date('M d, Y'),

                TextColumn::make('days')
                    ->label('Duration')
                    ->state(fn (Leave $record) => $record->getRequestedLeaveDays() . ' ' . \Illuminate\Support\Str::plural('day', $record->getRequestedLeaveDays()))
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-m-clock')
                    ->alignCenter(),

                TextColumn::make('current_approval_order')
                    ->label('Approval Level')
                    ->state(function (Leave $record): string {
                        $step = $record->approvalSteps->firstWhere('sequence', $record->current_approval_order)
                            ?? $record->approvalSteps->firstWhere('approver_employee_id', auth()->user()?->employee?->id);
                        return $step ? "L{$step->sequence}: {$step->label}" : $record->approval_label;
                    })
                    ->badge()
                    ->color('info'),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (Leave $record) => $record->approval_label)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        'Pending' => 'warning',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match ($state) {
                        'Approved' => 'heroicon-m-check-circle',
                        'Rejected' => 'heroicon-m-x-circle',
                        'Pending' => 'heroicon-m-clock',
                        default => 'heroicon-m-question-mark-circle',
                    }),

                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->since()
                    ->tooltip(fn (Leave $record) => $record->created_at->format('M d, Y h:i A')),
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
                Action::make('review')
                    ->label('Review')
                    ->icon(Heroicon::Eye)
                    ->color('primary')
                    ->url(fn (Leave $record) => ViewLeaveApproval::getUrl(['leaveId' => $record->publicKey()])),
            ])
            ->emptyStateHeading('No leave requests in this queue')
            ->emptyStateDescription('There are no leave requests under this category awaiting your review.')
            ->emptyStateIcon(Heroicon::ClipboardDocumentCheck);
    }
}
