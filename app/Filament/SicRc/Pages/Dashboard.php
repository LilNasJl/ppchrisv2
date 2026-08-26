<?php

namespace App\Filament\SicRc\Pages;

use App\Filament\SicRc\Widgets\SicRcBranchAttendanceChart;
use App\Models\Employee;
use App\Models\SicRcAccount;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Override;

class Dashboard extends Page
{
    protected string $view = 'filament.sicrc.pages.dashboard';

    protected static ?string $title = 'Dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Squares2x2;

    protected static ?int $navigationSort = 1;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function account(): ?SicRcAccount
    {
        $account = auth('sicrc')->user();

        return $account instanceof SicRcAccount ? $account : null;
    }

    public function branchCount(): int
    {
        return count($this->assignedBranchIds());
    }

    public function employeeCount(): int
    {
        $branchIds = $this->assignedBranchIds();

        if ($branchIds === []) {
            return 0;
        }

        return Employee::query()
            ->whereIn('branch_id', $branchIds)
            ->activeEmployment()
            ->count();
    }

    #[Override]
    protected function getFooterWidgets(): array
    {
        return [
            SicRcBranchAttendanceChart::class,
        ];
    }

    protected function assignedBranchIds(): array
    {
        return $this->account()?->assignedBranchIds() ?? [];
    }
}
