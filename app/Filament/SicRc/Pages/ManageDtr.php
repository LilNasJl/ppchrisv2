<?php

namespace App\Filament\SicRc\Pages;

use App\Filament\SicRc\Widgets\SicRcDtrManageTable;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\SicRcAccount;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Override;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ManageDtr extends Page
{
    protected string $view = 'filament-panels::pages.page';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Manage D.T.R';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Clock;

    public ?int $employeeId = null;

    public ?int $branchId = null;

    public ?int $periodId = null;

    public ?Employee $employee = null;

    public ?Branch $branch = null;

    public ?PayrollPeriod $period = null;

    public function mount(): void
    {
        $this->employeeId = Employee::resolvePublicId(request()->query('employeeId'));
        $this->branchId = Branch::resolvePublicId(request()->query('branchId'));
        $this->periodId = PayrollPeriod::resolvePublicId(request()->query('periodId'));

        $this->employee = $this->employeeId ? Employee::query()->find($this->employeeId) : null;
        $this->branch = $this->branchId ? Branch::query()->find($this->branchId) : null;
        $this->period = $this->periodId ? PayrollPeriod::query()->find($this->periodId) : null;

        if (! $this->branch || ! in_array($this->branch->id, $this->assignedBranchIds(), true)) {
            throw new HttpException(403, 'This branch is not attached to your SIC/RC account.');
        }

        if (! $this->employee || (int) $this->employee->branch_id !== (int) $this->branch->id) {
            throw new HttpException(403, 'This employee does not belong to the selected branch.');
        }

        if (! $this->period) {
            throw new HttpException(404, 'No payroll period was selected.');
        }
    }

    public function getTitle(): string
    {
        return 'Manage D.T.R - '.($this->employee?->full_name ?: 'Employee');
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function getWidgetData(): array
    {
        return [
            'employeeId' => $this->employeeId,
            'branchId' => $this->branchId,
            'periodId' => $this->periodId,
        ];
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(fn (): string => BranchEmployees::getUrl([
                    'branchId' => $this->branch?->publicKey(),
                    'periodId' => $this->period?->publicKey(),
                ])),
        ];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            SicRcDtrManageTable::class,
        ];
    }

    protected function account(): ?SicRcAccount
    {
        $account = auth('sicrc')->user();

        return $account instanceof SicRcAccount ? $account : null;
    }

    protected function assignedBranchIds(): array
    {
        return $this->account()?->assignedBranchIds() ?? [];
    }
}
