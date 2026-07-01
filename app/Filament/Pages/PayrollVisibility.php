<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use App\Models\User;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

class PayrollVisibility extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Payroll Visibility';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Eye;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => User::query()
                ->with(['employee.branch'])
                ->where('role', 'employee')
                ->whereHas('employee')
                ->leftJoin('employees as payroll_visibility_employees', 'payroll_visibility_employees.user_id', '=', 'users.id')
                ->select('users.*')
                ->orderBy('payroll_visibility_employees.uid'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                ImageColumn::make('profile_photo')
                    ->label('Profile')
                    ->getStateUsing(fn ($record): ?string => $record->profile_photo_url)
                    ->defaultImageUrl(fn (): string => url('/image/ppc_logo_circle.png'))
                    ->circular(),

                TextColumn::make('employee.uid')
                    ->label('ID No.')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => Employee::companyIdFromUid($state) ?? 'N/A')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereHas('employee', fn (Builder $employeeQuery): Builder => $employeeQuery
                            ->where('uid', 'like', '%'.preg_replace('/[^0-9]/', '', $search).'%'))),

                TextColumn::make('employee.full_name')
                    ->label('Employee Name')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereHas('employee', fn (Builder $employeeQuery): Builder => $employeeQuery
                            ->where('lastname', 'like', "%{$search}%")
                            ->orWhere('middlename', 'like', "%{$search}%")
                            ->orWhere('firstname', 'like', "%{$search}%")))
                    ->wrap(),

                TextColumn::make('employee.branch.branch_name')
                    ->label('Branch')
                    ->placeholder('-')
                    ->wrap(),

                ToggleColumn::make('can_view_payroll')
                    ->label('Payroll Visible')
                    ->onColor('success')
                    ->offColor('danger'),
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
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(Payroll::getUrl()),
        ];
    }
}
