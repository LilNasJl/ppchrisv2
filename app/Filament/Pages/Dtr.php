<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Dtr as DtrModel;
use App\Models\DtrChangeRequest;
use App\Models\DtrSubmission;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Services\DtrSynchronizationService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Url;
use Override;
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

    #[Url(as: 'periodId')]
    public mixed $periodId = null;

    #[Url(as: 'branchId')]
    public mixed $branchId = null;

    #[Url(as: 'search', except: '')]
    public string $search = '';

    #[Override]
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    #[Override]
    public function getHeading(): string
    {
        return '';
    }

    #[Override]
    public function getBreadcrumbs(): array
    {
        return [];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = 0;

        if (Schema::hasTable('dtr_submissions')) {
            $count += DtrSubmission::query()->where('is_new', true)->count();
        }

        if (Schema::hasTable('dtr_change_requests')) {
            $count += DtrChangeRequest::query()->pendingForHr()->count();
        }

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public function mount(): void
    {
        $requestedPeriodId = PayrollPeriod::resolvePublicId($this->periodId ?: request()->query('periodId'));
        $requestedBranchId = Branch::resolvePublicId($this->branchId ?: request()->query('branchId'));

        $defaultPeriodId = PayrollPeriod::query()->newestFirst()->value('id');
        $this->periodId = $requestedPeriodId ? (int) $requestedPeriodId : ($defaultPeriodId ? (int) $defaultPeriodId : null);
        $this->branchId = $requestedBranchId ? (int) $requestedBranchId : null;
        $this->search = trim((string) ($this->search ?: request()->query('search', '')));

        $this->reconcileUnsyncedIfAny();
    }

    public function updatedPeriodId(): void
    {
        $this->reconcileUnsyncedIfAny();
    }

    public function updatedBranchId(): void
    {
        $this->reconcileUnsyncedIfAny();
    }

    public function reconcileUnsyncedIfAny(): void
    {
        if ($this->periodId) {
            app(DtrSynchronizationService::class)->reconcileUnsyncedRecords($this->branchId, $this->periodId);
        }
    }

    public function syncStationRecords(): void
    {
        $count = app(DtrSynchronizationService::class)->reconcileUnsyncedRecords($this->branchId, $this->periodId);

        if ($count > 0) {
            Notification::make()
                ->title("Synchronized {$count} station DTR record(s)")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('All station DTR records are already synchronized')
                ->info()
                ->send();
        }
    }

    public function unsyncedStationRecordCount(): int
    {
        return app(DtrSynchronizationService::class)->countUnsyncedRecords($this->branchId, $this->periodId);
    }

    public function resetFilters(): void
    {
        $newestId = PayrollPeriod::query()->newestFirst()->value('id');
        $this->periodId = $newestId ? (int) $newestId : null;
        $this->branchId = null;
        $this->search = '';
    }

    /**
     * @return Collection<int, PayrollPeriod>
     */
    public function payrollPeriods(): Collection
    {
        return PayrollPeriod::query()
            ->newestFirst()
            ->get();
    }

    /**
     * @return Collection<int, Branch>
     */
    public function branches(): Collection
    {
        return Branch::query()
            ->orderBy('branch_name')
            ->get();
    }

    public function selectedPeriod(): ?PayrollPeriod
    {
        return filled($this->periodId)
            ? PayrollPeriod::query()->find((int) $this->periodId)
            : null;
    }

    public function selectedBranch(): ?Branch
    {
        return filled($this->branchId)
            ? Branch::query()->find((int) $this->branchId)
            : null;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function filteredEmployeeRows(): Collection
    {
        $period = $this->selectedPeriod();

        $query = Employee::query()
            ->with(['user', 'designation', 'department', 'branch'])
            ->activeEmployment()
            ->whereHas('user', fn (Builder $q): Builder => $q->where('role', 'employee'))
            ->when(filled($this->branchId), fn (Builder $q): Builder => $q->where('branch_id', (int) $this->branchId))
            ->when(trim($this->search) !== '', function (Builder $q): void {
                $term = '%'.trim($this->search).'%';
                $q->where(function (Builder $sub) use ($term): void {
                    $sub->where('lastname', 'like', $term)
                        ->orWhere('firstname', 'like', $term)
                        ->orWhere('middlename', 'like', $term)
                        ->orWhere('uid', 'like', $term)
                        ->orWhere('fingerprint_id', 'like', $term)
                        ->orWhereHas('designation', fn (Builder $dq): Builder => $dq->where('title', 'like', $term));
                });
            })
            ->orderBy('lastname')
            ->orderBy('firstname');

        $employees = $query->limit(150)->get();

        $dtrByFingerprint = collect();
        if ($period && $employees->isNotEmpty()) {
            $fingerprints = $employees
                ->map(fn (Employee $e): string => (string) ($e->fingerprint_id ?: $e->uid ?: ''))
                ->filter()
                ->unique()
                ->values();

            if ($fingerprints->isNotEmpty()) {
                $dtrByFingerprint = DtrModel::query()
                    ->where('payroll_period_id', $period->id)
                    ->whereIn('fingerprint_id', $fingerprints)
                    ->get([
                        'id',
                        'fingerprint_id',
                        'branch_id',
                        'overtime_status',
                        'overtime',
                        'early_clock_in',
                        'credited_work_hrs',
                        'credited_overtime',
                    ])
                    ->groupBy(fn (DtrModel $dtr): string => (string) $dtr->fingerprint_id);
            }
        }

        return $employees->map(function (Employee $employee) use ($period, $dtrByFingerprint): array {
            $fp = (string) ($employee->fingerprint_id ?: $employee->uid ?: '');
            $records = $fp !== '' ? ($dtrByFingerprint->get($fp) ?? collect()) : collect();

            $pendingOtCount = $records->filter(fn (DtrModel $r): bool => $r->overtime_status === 'Pending'
                && (((int) $r->overtime >= 30) || ((int) $r->early_clock_in >= 30)))->count();
            $approvedOtCount = $records->filter(fn (DtrModel $r): bool => $r->overtime_status === 'Approved')->count();

            $manageUrl = $period
                ? DtrManage::getUrl(array_filter([
                    'periodId' => $period->publicKey(),
                    'branchId' => $employee->branch?->publicKey() ?? $employee->branch_id,
                    'employeeId' => $employee->publicKey(),
                    'search' => filled($this->search) ? $this->search : null,
                ], fn ($val) => filled($val)))
                : '#';

            $printUrl = ($period && $employee->branch)
                ? route('dtr.print', [
                    'period' => $period->publicKey(),
                    'branch' => $employee->branch->publicKey(),
                    'employee' => $employee->publicKey(),
                ])
                : null;

            return [
                'employee' => $employee,
                'dtr_count' => $records->count(),
                'pending_ot_count' => $pendingOtCount,
                'approved_ot_count' => $approvedOtCount,
                'credited_work_hrs' => (int) $records->sum('credited_work_hrs'),
                'manage_url' => $manageUrl,
                'print_url' => $printUrl,
            ];
        });
    }

    /**
     * @return array{total_employees:int, employees_with_dtr:int, total_dtr_records:int, pending_ot_records:int}
     */
    public function periodSummary(Collection $rows): array
    {
        return [
            'total_employees' => $rows->count(),
            'employees_with_dtr' => $rows->filter(fn (array $row): bool => $row['dtr_count'] > 0)->count(),
            'total_dtr_records' => (int) $rows->sum('dtr_count'),
            'pending_ot_records' => (int) $rows->sum('pending_ot_count'),
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

    public function dtrChangeRequestCount(): int
    {
        if (! Schema::hasTable('dtr_change_requests')) {
            return 0;
        }

        return DtrChangeRequest::query()
            ->pendingForHr()
            ->count();
    }

    public function dtrChangeRequestsUrl(): string
    {
        return DtrChangeRequests::getUrl();
    }

    public function dtrImporterUrl(): string
    {
        return DtrViewer::getUrl();
    }
}
