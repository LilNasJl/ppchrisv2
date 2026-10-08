<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Override;

class PayrollVisibility extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected string $view = 'filament.pages.payroll-visibility';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Payroll Visibility';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Eye;

    public function getTitle(): string|Htmlable
    {
        return '';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(25)
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
                    ->rowIndex()
                    ->alignCenter(),

                ImageColumn::make('profile_photo')
                    ->label('Profile')
                    ->getStateUsing(fn ($record): ?string => $record->profile_photo_url)
                    ->defaultImageUrl(fn (): string => asset('image/ppc-circle-white.png'))
                    ->circular()
                    ->imageSize(28)
                    ->alignCenter(),

                TextColumn::make('employee.uid')
                    ->label('ID No.')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state): string => Employee::companyIdFromUid($state) ?? 'N/A')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderBy('payroll_visibility_employees.uid', $direction))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereHas('employee', fn (Builder $employeeQuery): Builder => $employeeQuery
                            ->where('uid', 'like', '%'.preg_replace('/[^0-9]/', '', $search).'%'))),

                TextColumn::make('employee.full_name')
                    ->label('Employee Name')
                    ->weight('bold')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderBy('payroll_visibility_employees.lastname', $direction)
                        ->orderBy('payroll_visibility_employees.firstname', $direction))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereHas('employee', fn (Builder $employeeQuery): Builder => $employeeQuery
                            ->where('lastname', 'like', "%{$search}%")
                            ->orWhere('middlename', 'like', "%{$search}%")
                            ->orWhere('firstname', 'like', "%{$search}%"))),

                TextColumn::make('employee.branch.branch_name')
                    ->label('Branch')
                    ->placeholder('-')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('visibility_status')
                    ->label('Status')
                    ->getStateUsing(fn (User $record): string => $record->can_view_payroll ? 'Visible' : 'Hidden')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Visible' ? 'success' : 'gray')
                    ->alignCenter(),

                ToggleColumn::make('can_view_payroll')
                    ->label('Payroll Visible')
                    ->onColor('success')
                    ->offColor('danger')
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->options(fn (): array => Branch::query()
                        ->orderBy('branch_name')
                        ->pluck('branch_name', 'id')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('employee', fn (Builder $employeeQuery): Builder => $employeeQuery
                            ->where('branch_id', $data['value']))
                        : $query),

                SelectFilter::make('can_view_payroll')
                    ->label('Visibility Status')
                    ->options([
                        '1' => 'Visible',
                        '0' => 'Hidden',
                    ]),
            ])
            ->toolbarActions([
                Action::make('return')
                    ->label('Return')
                    ->icon('heroicon-o-arrow-left')
                    ->color('gray')
                    ->url(Payroll::getUrl()),

                Action::make('enable_all')
                    ->label('Enable All')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Enable Payroll Visibility for All Employees')
                    ->modalDescription('Are you sure you want to make payroll visible to all employees?')
                    ->action(function (): void {
                        User::query()
                            ->where('role', 'employee')
                            ->whereHas('employee')
                            ->update(['can_view_payroll' => true]);

                        Notification::make()
                            ->title('Payroll visibility enabled for all employees')
                            ->success()
                            ->send();
                    }),

                Action::make('disable_all')
                    ->label('Disable All')
                    ->icon('heroicon-o-eye-slash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Disable Payroll Visibility for All Employees')
                    ->modalDescription('Are you sure you want to hide payroll from all employees?')
                    ->action(function (): void {
                        User::query()
                            ->where('role', 'employee')
                            ->whereHas('employee')
                            ->update(['can_view_payroll' => false]);

                        Notification::make()
                            ->title('Payroll visibility disabled for all employees')
                            ->warning()
                            ->send();
                    }),
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
        return [];
    }
}
