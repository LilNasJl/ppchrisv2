<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\HolidayExclusionTable;
use App\Models\Branch;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;

class HolidayExclusions extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.branch-holiday-branches';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Holiday Exclusions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserMinus;

    public ?int $branchId = null;

    public ?Branch $branch = null;

    public function mount(): void
    {
        $this->branchId = Branch::resolvePublicId(request()->query('branchId'));
        $this->branch = filled($this->branchId) ? Branch::query()->find($this->branchId) : null;
    }

    public function getTitle(): string
    {
        return $this->branch?->branch_name
            ? 'Holiday Exclusions - '.$this->branch->branch_name
            : 'National Holiday Exclusions';
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(fn (): string => $this->branch
                    ? BranchHolidayCalendar::getUrl(['branchId' => $this->branch->publicKey()])
                    : HolidayCalendar::getUrl()),
        ];
    }

    public function getWidgetData(): array
    {
        return [
            'branchId' => $this->branchId,
        ];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            HolidayExclusionTable::class,
        ];
    }
}
