<?php

namespace App\Filament\Employee\Pages\Station;

use App\Models\Branch;
use App\Models\PayrollPeriod;
use App\Services\StationManagementAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StationDtrImport extends Page
{
    protected string $view = 'filament.employee.pages.station.station-dtr-import';

    protected static ?string $slug = 'station/dtr-import';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Import Station D.T.R';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowUpTray;

    public ?int $branchId = null;

    public ?int $periodId = null;

    public ?Branch $branch = null;

    public ?PayrollPeriod $period = null;

    public static function canAccess(): bool
    {
        return StationManagementAccess::canAccessStationManagement(auth()->user());
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function mount(): void
    {
        if (! StationManagementAccess::canAccessStationManagement(auth()->user())) {
            throw new HttpException(403, 'Unauthorized access to Station Management.');
        }

        $this->branchId = Branch::resolvePublicId(request()->query('branchId'));
        $this->periodId = PayrollPeriod::resolvePublicId(request()->query('periodId'));

        $this->branch = $this->branchId ? Branch::query()->find($this->branchId) : null;
        $this->period = $this->periodId ? PayrollPeriod::query()->find($this->periodId) : null;

        if (! $this->branch || ! StationManagementAccess::canManageBranch(auth()->user(), $this->branch->id)) {
            throw new HttpException(403, 'This station branch is not assigned to your management profile.');
        }

        if (! $this->period) {
            throw new HttpException(404, 'No payroll period was selected.');
        }
    }

    public function getTitle(): string
    {
        return 'Import D.T.R - '.($this->branch?->branch_name ?: 'Branch');
    }

    public function getIframeUrl(): string
    {
        return asset('page/hr_atttendance_viewer.html').'?'.http_build_query([
            'endpoint' => route('sicrc_tools.import.dtr_preview'),
            'period_id' => $this->periodId,
            'branch_id' => $this->branchId,
            'dtr_only' => 1,
        ]);
    }

    /**
     * @return array<int, array{value:string, label:string}>
     */
    public function getPayrollPeriodOptions(): array
    {
        return $this->period ? [[
            'value' => (string) $this->period->getKey(),
            'label' => (string) $this->period->title,
        ]] : [];
    }

    /**
     * @return array<int, array{value:string, label:string}>
     */
    public function getBranchOptions(): array
    {
        return $this->branch ? [[
            'value' => (string) $this->branch->getKey(),
            'label' => (string) $this->branch->branch_name,
        ]] : [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importHistory')
                ->label('DTR Import History')
                ->icon(Heroicon::Clock)
                ->url(fn (): string => StationDtrImportHistory::getUrl([
                    'branchId' => $this->branch?->publicKey(),
                    'periodId' => $this->period?->publicKey(),
                ])),

            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(fn (): string => StationEmployees::getUrl([
                    'branchId' => $this->branch?->publicKey(),
                    'periodId' => $this->period?->publicKey(),
                ])),
        ];
    }
}
