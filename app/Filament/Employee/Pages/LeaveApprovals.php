<?php

namespace App\Filament\Employee\Pages;

use App\Models\Leave;
use App\Models\LeaveRequestApproval;
use App\Services\LeaveApprovalAccess;
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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeaveApprovals extends Page implements HasTable
{
    use HasTabs;
    use InteractsWithTable;

    protected string $view = 'filament.employee.pages.table-page';
    protected static ?string $title = 'Leave Approvals';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentCheck;
    protected static string|\UnitEnum|null $navigationGroup = 'My Workspace';
    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return LeaveApprovalAccess::inbox(auth()->user());
    }

    public static function getNavigationBadge(): ?string
    {
        $count = LeaveRequestApproval::where('approver_employee_id', auth()->user()?->employee?->id)
            ->where('status', 'Pending')->whereHas('leave', fn ($q) => $q->where('status', 'Pending'))->count();

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
            ->whereHas('approvalSteps', fn ($q) => $q->where('approver_employee_id', auth()->user()->employee->id)->whereNotNull('activated_at'))))
            ->defaultSort('created_at', 'desc')->poll('30s')
            ->columns([
                TextColumn::make('employee.lastname')->label('Employee')->formatStateUsing(fn (Leave $record) => $record->approval_snapshot['employee_name'] ?? $record->employee?->full_name)
                    ->description(fn (Leave $record) => $record->approval_snapshot['branch'] ?? $record->employee?->branch?->branch_name)
                    ->searchable(['firstname', 'lastname']),
                TextColumn::make('leave_type')->label('Leave Type'),
                TextColumn::make('leave_from')->label('From')->date('M d, Y'),
                TextColumn::make('leave_to')->label('To')->date('M d, Y'),
                TextColumn::make('days')->state(fn (Leave $record) => $record->getRequestedLeaveDays()),
                TextColumn::make('status')->formatStateUsing(fn (Leave $record) => $record->approval_label)->badge()
                    ->color(fn ($state) => match ($state) { 'Approved' => 'success', 'Rejected' => 'danger', 'Pending' => 'warning', default => 'gray' }),
                TextColumn::make('created_at')->label('Submitted')->since()->tooltip(fn (Leave $record) => $record->created_at->format('M d, Y h:i A')),
            ])->recordActions([
                Action::make('review')->label('View / Review')->icon(Heroicon::Eye)->button()
                    ->url(fn (Leave $record) => ViewLeaveApproval::getUrl(['leaveId' => $record->publicKey()])),
            ])->emptyStateHeading('No leave requests in this queue');
    }
}
