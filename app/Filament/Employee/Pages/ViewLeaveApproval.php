<?php

namespace App\Filament\Employee\Pages;

use App\Filament\Support\LeaveReviewActions;
use App\Models\Leave;
use App\Services\LeaveApprovalAccess;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Locked;

class ViewLeaveApproval extends Page
{
    protected string $view = 'filament-panels::pages.page';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'leave-approvals/view';
    protected static ?string $title = 'Review Leave Request';

    #[Locked]
    public Leave $leave;

    public static function canAccess(): bool
    {
        return LeaveApprovalAccess::inbox(auth()->user());
    }

    public function mount(): void
    {
        $this->leave = Leave::findOrFail(Leave::resolvePublicId(request('leaveId')));
        abort_unless(static::canAccess() && LeaveApprovalAccess::view(auth()->user(), $this->leave), 403);
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function content(Schema $schema): Schema
    {
        abort_unless(LeaveApprovalAccess::view(auth()->user(), $this->leave), 403);

        return $schema->components([View::make('filament.employee.pages.partials.leave-request-details')->viewData(['leave' => $this->leave->fresh()])]);
    }

    protected function getHeaderActions(): array
    {
        return [...LeaveReviewActions::make($this->leave, false),
            Action::make('return')->label('Return')->icon(Heroicon::ArrowLeft)->color('gray')->url(LeaveApprovals::getUrl())];
    }
}
