<?php

namespace App\Filament\Resources\PayrollPeriods\Pages;

use App\Filament\Pages\Payroll;
use App\Filament\Resources\PayrollPeriods\PayrollPeriodResource;
use App\Filament\Resources\PayrollPeriods\Widgets\PayrollPeriodStatsOverview;
use App\Models\PayrollPeriod;
use App\Services\PayrollPeriodGenerator;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ListPayrollPeriods extends ListRecords
{
    protected static string $resource = PayrollPeriodResource::class;

    protected string $view = 'filament.resources.payroll-periods.pages.list-payroll-periods';

    public function mount(): void
    {
        parent::mount();

        PayrollPeriod::query()
            ->where('is_locked', true)
            ->where('description', 'Payroll currently running')
            ->update(['description' => 'Payroll not currently running']);

        PayrollPeriod::query()
            ->where('is_locked', false)
            ->where('description', 'Payroll not currently running')
            ->update(['description' => 'Payroll currently running']);
    }

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

    public function getTabs(): array
    {
        $today = now('Asia/Manila')->toDateString();

        return [
            'all' => Tab::make('All Periods')
                ->icon('heroicon-o-list-bullet')
                ->badge(fn (): int => PayrollPeriod::query()->count()),

            'open' => Tab::make('Open / Active')
                ->icon('heroicon-o-lock-open')
                ->badge(fn (): int => PayrollPeriod::query()->where('is_locked', false)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_locked', false)),

            'locked' => Tab::make('Locked')
                ->icon('heroicon-o-lock-closed')
                ->badge(fn (): int => PayrollPeriod::query()->where('is_locked', true)->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_locked', true)),

            'due' => Tab::make('Due for Lock')
                ->icon('heroicon-o-clock')
                ->badge(fn (): int => PayrollPeriod::query()
                    ->where('is_locked', false)
                    ->whereDate('date_payout', '<', $today)
                    ->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('is_locked', false)
                    ->whereDate('date_payout', '<', $today)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
