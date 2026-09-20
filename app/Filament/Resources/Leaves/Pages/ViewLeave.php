<?php

namespace App\Filament\Resources\Leaves\Pages;

use App\Filament\Resources\Leaves\LeaveResource;
use App\Filament\Support\LeaveReviewActions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ViewLeave extends ViewRecord
{
    protected static string $resource = LeaveResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([View::make('filament.employee.pages.partials.leave-request-details')
            ->viewData(['leave' => $this->getRecord()->fresh()])]);
    }

    protected function getHeaderActions(): array
    {
        return [...LeaveReviewActions::make($this->getRecord(), true),
            Action::make('return')->label('Return')->icon(Heroicon::ArrowLeft)->color('gray')->url(LeaveResource::getUrl())];
    }
}
