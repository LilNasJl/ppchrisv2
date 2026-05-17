<?php

namespace App\Filament\Resources\Deductions\Pages;

use App\Filament\Resources\Deductions\DeductionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Query\Builder;

class ListDeductions extends ListRecords
{
    protected static string $resource = DeductionResource::class;

    // public function getTabs(): array
    // {
    //     return [
    //         'all' => Tab::make('All'),
    //         'active' => Tab::make('Active')
    //             ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)),
    //         'inactive' => Tab::make('Inactive')
    //             ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),
    //     ];
    // }


    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
