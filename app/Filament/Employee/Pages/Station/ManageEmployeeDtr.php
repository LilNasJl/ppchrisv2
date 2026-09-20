<?php

namespace App\Filament\Employee\Pages\Station;

use App\Filament\Employee\Widgets\StationDtrManageTable;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Services\StationManagementAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Override;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ManageEmployeeDtr extends Page
{
    protected string $view = 'filament-panels::pages.page';

    protected static ?string $slug = 'station/manage-dtr';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Manage Employee D.T.R';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Clock;

    public ?int $employeeId = null;

    public ?int $branchId = null;

    public ?int $periodId = null;

    public ?Employee $employee = null;

    public ?Branch $branch = null;

    public ?PayrollPeriod $period = null;

    public static function canAccess(): bool
    {
        return StationManagementAccess::canAccessStationManagement(auth()->user());
    }

    public function mount(): void
    {
        if (! StationManagementAccess::canAccessStationManagement(auth()->user())) {
            throw new HttpException(403, 'Unauthorized access to Station Management.');
        }

        $this->employeeId = Employee::resolvePublicId(request()->query('employeeId'));
        $this->branchId = Branch::resolvePublicId(request()->query('branchId'));
        $this->periodId = PayrollPeriod::resolvePublicId(request()->query('periodId'));

        $this->employee = $this->employeeId ? Employee::query()->find($this->employeeId) : null;
        $this->branch = $this->branchId ? Branch::query()->find($this->branchId) : null;
        $this->period = $this->periodId ? PayrollPeriod::query()->find($this->periodId) : null;

        if (! $this->branch || ! StationManagementAccess::canManageBranch(auth()->user(), $this->branch->id)) {
            throw new HttpException(403, 'This station branch is not assigned to your management profile.');
        }

        if (! $this->employee || ! StationManagementAccess::canManageEmployee(auth()->user(), $this->employee)) {
            throw new HttpException(403, 'This employee is not under your assigned station branches.');
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
                ->url(fn (): string => StationEmployees::getUrl([
                    'branchId' => $this->branch?->publicKey(),
                    'periodId' => $this->period?->publicKey(),
                ])),
        ];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            StationDtrManageTable::class,
        ];
    }
}
