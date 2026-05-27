<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\PayrollPeriod;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Group;
use Filament\Support\Icons\Heroicon;
use Override;

class DtrViewer extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected string $view = 'filament.pages.dtr-viewer';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'D.T.R Viewer';

    public ?string $period_id = null;

    public ?string $branch_id = null;

    public function mount(): void
    {
        $this->form->fill([
            'period_id' => PayrollPeriod::query()
                ->where('is_locked', false)
                ->newestFirst()
                ->value('id'),
            'branch_id' => Branch::query()->latest()->value('id'),
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Group::make([
                Select::make('period_id')
                    ->label('Payroll Period')
                    ->options(
                        PayrollPeriod::query()
                            ->where('is_locked', false)
                            ->newestFirst()
                            ->pluck('title', 'id')
                    )
                    ->searchable()
                    ->reactive(),

                Select::make('branch_id')
                    ->label('Branch')
                    ->options(
                        Branch::query()
                            ->pluck('branch_name', 'id')
                    )
                    ->searchable()
                    ->reactive(),
            ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    #[Override]
    public function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(Dtr::getUrl()),

        ];
    }
}
