<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\DtrManage;
use App\Models\Employee;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class DtrBranchEmployeeTable extends TableWidget
{
    use HasWidgetShield;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Employees';

    public ?int $periodId = null;

    public ?int $branchId = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Employee::query()
                ->with(['user', 'designation', 'branch'])
                ->activeEmployment()
                ->where('branch_id', $this->branchId)
                ->whereHas('user', fn (Builder $query) => $query->where('role', 'employee'))
                ->orderBy('uid'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                ImageColumn::make('profile_photo')
                    ->label('Profile')
                    ->getStateUsing(fn (Employee $record): ?string => $record->user?->profile_photo_url)
                    ->defaultImageUrl(fn (): string => url('/image/ppc_logo_circle.png'))
                    ->circular(),

                TextColumn::make('uid')
                    ->label('ID No.')
                    ->badge()
                    ->formatStateUsing(fn (Employee $record): string => $record->company_id ?? 'N/A'),

                TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['lastname', 'middlename', 'firstname'])
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderBy('lastname', $direction)
                        ->orderBy('middlename', $direction)
                        ->orderBy('firstname', $direction)),

                TextColumn::make('designation.title')
                    ->label('Designation')
                    ->badge()
                    ->searchable()
                    ->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('manageDtr')
                        ->label('Manage D.T.R')
                        ->icon(Heroicon::Cog6Tooth)
                        ->url(fn (Employee $record): string => DtrManage::getUrl([
                            'periodId' => $this->periodId,
                            'branchId' => $record->branch_id,
                            'employeeId' => $record->id,
                        ])),
                ])
                    ->icon(Heroicon::EllipsisHorizontal),
            ]);
    }
}
