<?php

namespace App\Filament\Pages;

use App\Models\DtrSubmission;
use App\Models\PayrollPeriod;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Schema;
use UnitEnum;

class Dtr extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected string $view = 'filament.pages.dtr';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::QueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Compensation and Benefits Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Daily Time Record';

    protected static ?string $title = 'D.T.R Management';

    public ?int $periodId = null;

    public static function getNavigationBadge(): ?string
    {
        if (! Schema::hasTable('dtr_submissions')) {
            return null;
        }

        $count = DtrSubmission::query()->where('is_new', true)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public function mount(): void
    {
        $this->form->fill([
            'periodId' => null,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('periodId')
                ->label('Search Payroll Period')
                ->options(fn (): array => PayrollPeriod::query()
                    ->newestFirst()
                    ->get()
                    ->mapWithKeys(fn (PayrollPeriod $period): array => [
                        $period->id => trim($period->title.' - '.($period->is_locked ? 'Locked' : 'Open')),
                    ])
                    ->all())
                ->searchable()
                ->preload()
                ->placeholder('Select payroll period')
                ->live()
                ->afterStateUpdated(function ($state): void {
                    $period = filled($state) ? PayrollPeriod::query()->find((int) $state) : null;

                    if (! $period) {
                        return;
                    }

                    $this->redirect(DtrPeriodBranches::getUrl([
                        'periodId' => $period->publicKey(),
                    ]));
                }),
        ];
    }

    public function dtrSubmissionCount(): int
    {
        if (! Schema::hasTable('dtr_submissions')) {
            return 0;
        }

        $query = DtrSubmission::query()->where('is_new', true);

        if (Schema::hasColumn('dtr_submissions', 'submission_type')) {
            $query->where('submission_type', DtrSubmission::TYPE_DTR);
        }

        return $query->count();
    }

    public function dtrSubmissionsUrl(): string
    {
        return DtrSubmissions::getUrl();
    }

    public function dtrProofSubmissionCount(): int
    {
        if (! Schema::hasTable('dtr_submissions') || ! Schema::hasColumn('dtr_submissions', 'submission_type')) {
            return 0;
        }

        return DtrSubmission::query()
            ->where('submission_type', DtrSubmission::TYPE_PROOF)
            ->where('is_new', true)
            ->count();
    }

    public function dtrProofSubmissionsUrl(): string
    {
        return DtrProofSubmissions::getUrl();
    }

    public function dtrImporterUrl(): string
    {
        return DtrViewer::getUrl();
    }
}
