<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Override;

class BranchHolidayCalendar extends HolidayCalendar
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Branch Holiday Calendar';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CalendarDays;

    public ?int $branchId = null;

    public ?Branch $branch = null;

    public function mount(): void
    {
        $this->branchId = (int) request()->query('branchId');
        $this->branch = Branch::query()->find($this->branchId);

        parent::mount();
    }

    public function getTitle(): string
    {
        return $this->branch?->branch_name
            ? 'Branch Holidays - '.$this->branch->branch_name
            : 'Branch Holiday Calendar';
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(BranchHolidayBranches::getUrl()),
            ...parent::getHeaderActions(),
        ];
    }

    protected function getHolidayBranchId(): ?int
    {
        return $this->branchId;
    }

    protected function isBranchHolidayCalendar(): bool
    {
        return true;
    }

    public function getCalendarScopeLabelProperty(): string
    {
        return $this->branch?->branch_name ?: 'Branch';
    }
}
