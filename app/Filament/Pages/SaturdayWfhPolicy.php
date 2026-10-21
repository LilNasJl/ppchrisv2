<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use App\Models\SaturdayOnSiteExemption;
use App\Services\SaturdayWfhPolicyService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Override;

class SaturdayWfhPolicy extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.saturday-wfh-policy';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Saturday WFH Policy';

    public static function canAccess(): bool
    {
        return auth()->check() && in_array(auth()->user()->role, ['hr', 'admin'], true) && Dtr::canAccess();
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    #[Override]
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    #[Override]
    public function getHeading(): string
    {
        return '';
    }

    #[Override]
    public function getHeader(): ?View
    {
        return view('filament.pages.saturday-wfh-header');
    }

    #[Override]
    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function isPolicyEnabled(): bool
    {
        return app(SaturdayWfhPolicyService::class)->isEnabled();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(SaturdayOnSiteExemption::query()->with(['employee.branch']))
            ->defaultSort('effective_from', 'desc')
            ->columns([
                TextColumn::make('row_number')->label('#')->rowIndex(),
                TextColumn::make('employee.uid')->label('Employee ID')->searchable()
                    ->formatStateUsing(fn ($state): string => Employee::companyIdFromUid($state) ?? '—'),
                TextColumn::make('employee_name')->label('Employee')
                    ->state(fn (SaturdayOnSiteExemption $record): string => $record->employee?->full_name ?: 'Deleted employee')
                    ->weight('bold'),
                TextColumn::make('employee.branch.branch_name')->label('Branch')->placeholder('—'),
                TextColumn::make('effective_from')->label('On-site from')->date('M d, Y')->sortable(),
                TextColumn::make('effective_to')->label('On-site until')->date('M d, Y')->placeholder('Permanent'),
            ])
            ->recordActions([
                Action::make('returnToWfh')
                    ->label('Return to WFH')
                    ->icon(Heroicon::ArrowUturnLeft)
                    ->visible(fn (SaturdayOnSiteExemption $record): bool => $this->canEndEarly($record))
                    ->disabled(fn (): bool => ! $this->isPolicyEnabled())
                    ->modalHeading('End Saturday on-site assignment')
                    ->modalDescription('This employee will return to Saturday WFH effective today. Past attendance stays unchanged.')
                    ->requiresConfirmation()
                    ->action(function (SaturdayOnSiteExemption $record): void {
                        abort_unless(static::canAccess(), 403);
                        try {
                            app(SaturdayWfhPolicyService::class)->endOnSiteExemption(
                                $record->employee, $record->id, today('Asia/Manila')->toDateString(), auth()->user(),
                            );
                        } catch (InvalidArgumentException $exception) {
                            Notification::make()->title('Return to WFH was not saved')
                                ->body($exception->getMessage())->danger()->send();
                            throw new Halt;
                        }
                        Notification::make()->title('Return to Saturday WFH scheduled')->success()->send();
                    }),
            ])
            ->emptyStateHeading('No employees assigned on-site Saturdays')
            ->emptyStateDescription('Regular-schedule employees follow Saturday WFH by default once the policy starts.');
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return to DTR')
                ->icon(Heroicon::ArrowLeft)
                ->color('gray')
                ->url(Dtr::getUrl()),
            Action::make('policySettings')
                ->label('Saturday WFH Settings')
                ->icon(Heroicon::Cog6Tooth)
                ->color('gray')
                ->modalHeading('Saturday WFH Settings')
                ->modalDescription('Enable or disable Saturday WFH for all regular-schedule employees. Changes apply from now on; past attendance remains unchanged.')
                ->modalSubmitActionLabel('Save Settings')
                ->fillForm(fn (): array => ['enabled' => $this->isPolicyEnabled()])
                ->schema([
                    Toggle::make('enabled')->label('Enable Saturday WFH')
                        ->helperText('When disabled, Saturday WFH and employee on-site assignments do not apply.'),
                    TextInput::make('password')->label('Current account password')
                        ->password()->required()->autocomplete('current-password'),
                ])
                ->action(function (array $data): void {
                    abort_unless(static::canAccess(), 403);
                    if (! Hash::check((string) ($data['password'] ?? ''), auth()->user()->password)) {
                        Notification::make()->title('Incorrect account password')->danger()->send();
                        throw new Halt;
                    }
                    $policy = app(SaturdayWfhPolicyService::class);
                    $enable = (bool) ($data['enabled'] ?? false);
                    try {
                        if ($enable) {
                            if (! $policy->isEnabled() || $policy->effectiveFrom() > today('Asia/Manila')->toDateString()) {
                                $policy->enable(auth()->user());
                            }
                        } elseif ($policy->isEnabled()) {
                            $policy->stop(auth()->user());
                        }
                    } catch (InvalidArgumentException $exception) {
                        Notification::make()->title('Saturday WFH settings were not saved')
                            ->body($exception->getMessage())->danger()->send();
                        throw new Halt;
                    }
                    Notification::make()->title($enable ? 'Saturday WFH enabled' : 'Saturday WFH disabled')
                        ->success()->send();
                }),
            Action::make('addOnSite')
                ->label('Add Saturday On-site')
                ->icon(Heroicon::Plus)
                ->disabled(fn (): bool => ! $this->isPolicyEnabled())
                ->modalHeading('Assign employees to on-site Saturdays')
                ->modalDescription('Select monthly, regular-schedule employees. Their on-site assignment starts today and remains in effect until HR returns them to WFH.')
                ->modalSubmitActionLabel('Save Assignments')
                ->schema([
                    Select::make('employee_ids')->label('Employees')
                        ->multiple()->searchable()->preload()->required()
                        ->options(fn (): array => Employee::query()->activeEmployment()
                            ->where('rate_type', 'monthly')->where('schedule_type', 'regular')
                            ->orderBy('lastname')->orderBy('firstname')->get()
                            ->mapWithKeys(fn (Employee $employee): array => [
                                $employee->id => $employee->company_id.' · '.$employee->full_name,
                            ])->all()),
                ])
                ->action(function (array $data): void {
                    abort_unless(static::canAccess(), 403);
                    try {
                        $ids = array_values(array_unique(array_map('intval', $data['employee_ids'] ?? [])));
                        $employees = Employee::query()->activeEmployment()
                            ->where('rate_type', 'monthly')->where('schedule_type', 'regular')
                            ->whereIn('id', $ids)->get();
                        if ($ids === [] || $employees->count() !== count($ids)) {
                            throw new InvalidArgumentException('Select active monthly, regular-schedule employees.');
                        }
                        DB::transaction(function () use ($employees): void {
                            foreach ($employees as $employee) {
                                app(SaturdayWfhPolicyService::class)->addOnSiteExemption(
                                    $employee, today('Asia/Manila')->toDateString(), null, null, auth()->user(),
                                );
                            }
                        });
                    } catch (InvalidArgumentException $exception) {
                        Notification::make()->title('Saturday on-site assignment was not saved')
                            ->body($exception->getMessage())->danger()->send();
                        throw new Halt;
                    }
                    Notification::make()->title('Saturday on-site assignments saved')->success()->send();
                }),
        ];
    }

    private function canEndEarly(SaturdayOnSiteExemption $record): bool
    {
        return $record->employee !== null
            && ($record->effective_to === null
                || $record->effective_to->toDateString() >= today('Asia/Manila')->toDateString());
    }
}
