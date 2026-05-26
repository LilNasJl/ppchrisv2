<?php

namespace App\Filament\Pages;

use App\Filament\Resources\MemoTypes\MemoTypeResource;
use App\Models\Employee;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;
use UnitEnum;

class Memo extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static ?string $title = 'Memo';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Reports and Documents';

    protected static ?int $navigationSort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Employee::query()
                ->with(['user', 'designation', 'department', 'branch'])
                ->whereHas('user', fn (Builder $query) => $query->where('role', 'employee'))
                ->orderBy('lastname')
                ->orderBy('firstname'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                ImageColumn::make('user.profile_photo_path')
                    ->label('Profile')
                    ->disk('public')
                    ->circular(),

                TextColumn::make('full_name')
                    ->label('Employee Name')
                    ->badge()
                    ->searchable(['lastname', 'middlename', 'firstname'])
                    ->sortable(['lastname', 'firstname']),

                TextColumn::make('designation.title')
                    ->label('Designation')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('department.name')
                    ->label('Department')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('branch.branch_name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('employment_type')
                    ->label('Employment Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, Employee $record): string => $record->hasEndedEmployment()
                            ? "Employment End: {$state}"
                            : ($state ?: 'Active')
                    )
                    ->color(fn (Employee $record): string => $record->hasEndedEmployment() ? 'danger' : 'success'),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('viewMemo')
                        ->label('View Memo')
                        ->icon(Heroicon::DocumentText)
                        ->url(fn (Employee $record): string => EmployeeMemo::getUrl([
                            'employeeId' => $record->publicKey(),
                        ])),
                ])
                    ->icon(Heroicon::EllipsisHorizontal),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('manageMemoTypes')
                ->label('Manage Memo Types')
                ->icon(Heroicon::Cog6Tooth)
                ->url(MemoTypeResource::getUrl()),
        ];
    }
}
