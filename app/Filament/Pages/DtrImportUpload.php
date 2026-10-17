<?php

namespace App\Filament\Pages;

use App\Models\PayrollPeriod;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;

class DtrImportUpload extends Page
{
    protected string $view = 'filament.pages.dtr-import-upload';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Import D.T.R Entries';

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function getPayrollPeriodOptions(): array
    {
        return PayrollPeriod::query()
            ->where('is_locked', false)
            ->newestFirst()
            ->get(['id', 'title'])
            ->map(fn (PayrollPeriod $period): array => [
                'value' => (string) $period->getKey(),
                'label' => (string) $period->title,
            ])
            ->all();
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(DtrImport::getUrl()),
        ];
    }
}
