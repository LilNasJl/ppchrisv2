<?php

namespace App\Filament\Resources\Leaves\Pages;

use App\Filament\Resources\Leaves\LeaveResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListLeaves extends ListRecords
{
    protected static string $resource = LeaveResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),

            'ready' => Tab::make('Ready for HR')
                ->badge(fn () => \App\Models\Leave::where('status', 'Pending')->where(fn ($q) => $q->whereNull('approval_workflow_id')->orWhere('approval_phase', 'hr'))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Pending')->where(fn ($q) => $q->whereNull('approval_workflow_id')->orWhere('approval_phase', 'hr'))),
            'preliminary' => Tab::make('Awaiting Approvers')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Pending')->where('approval_phase', 'preliminary')),

            'draft' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Approved')),

            'archived' => Tab::make('Rejected')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Rejected')),
            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Cancelled')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Leave Request')
                ->icon(Heroicon::PaperAirplane),
            \Filament\Actions\Action::make('workflows')->label('Approval Workflows')->icon(Heroicon::AdjustmentsHorizontal)->color('gray')
                ->visible(fn () => \App\Services\LeaveApprovalAccess::configure(auth()->user()))
                ->url(\App\Filament\Pages\LeaveApprovalWorkflows::getUrl()),
        ];
    }
}
