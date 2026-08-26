<?php

namespace App\Filament\SicRc\Pages;

use App\Models\Branch;
use App\Models\PayrollPeriod;
use App\Models\SicRcAccount;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DtrImportUpload extends Page
{
    protected string $view = 'filament.sicrc.pages.dtr-import-upload';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Import D.T.R';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowUpTray;

    public ?int $branchId = null;

    public ?int $periodId = null;

    public ?Branch $branch = null;

    public ?PayrollPeriod $period = null;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function mount(): void
    {
        $this->branchId = Branch::resolvePublicId(request()->query('branchId'));
        $this->periodId = PayrollPeriod::resolvePublicId(request()->query('periodId'));

        $this->branch = $this->branchId ? Branch::query()->find($this->branchId) : null;
        $this->period = $this->periodId ? PayrollPeriod::query()->find($this->periodId) : null;

        if (! $this->branch || ! in_array($this->branch->id, $this->assignedBranchIds(), true)) {
            throw new HttpException(403, 'This branch is not attached to your SIC/RC account.');
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
                ->url(fn (): string => DtrImportHistory::getUrl([
                    'branchId' => $this->branch?->publicKey(),
                    'periodId' => $this->period?->publicKey(),
                ], panel: 'sicrc')),

            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(fn (): string => BranchEmployees::getUrl([
                    'branchId' => $this->branch?->publicKey(),
                    'periodId' => $this->period?->publicKey(),
                ], panel: 'sicrc')),
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
