<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\InteractsWithOnFieldDtrEdit;
use App\Models\Branch;
use App\Models\DtrSubmission;
use App\Models\PayrollPeriod;
use App\Services\OnFieldDtrService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Pages\Page;
use Filament\Resources\Concerns\HasTabs;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Illuminate\Support\Facades\Storage;
use Override;

class DtrProofSubmissions extends Page implements HasTable
{
    use HasPageShield {
        canAccess as protected canAccessWithShield;
    }
    use HasTabs;
    use InteractsWithOnFieldDtrEdit;
    use InteractsWithTable;

    protected string $view = 'filament.pages.dtr-proof-submissions';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'On Field DTR';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentCheck;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return static::canAccessWithShield() && $user !== null && in_array($user->role, ['hr', 'admin'], true);
    }

    public function mount(): void
    {
        $this->loadDefaultActiveTab();
    }

    #[Override]
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'all';
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn (): ?int => $this->getTabCount(null)),

            'pending' => Tab::make('Pending')
                ->badge(fn (): ?int => $this->getTabCount(DtrSubmission::STATUS_PENDING))
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', DtrSubmission::STATUS_PENDING)),

            'approved' => Tab::make('Approved')
                ->badge(fn (): ?int => $this->getTabCount(DtrSubmission::STATUS_APPROVED))
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', DtrSubmission::STATUS_APPROVED)),

            'rejected' => Tab::make('Rejected')
                ->badge(fn (): ?int => $this->getTabCount(DtrSubmission::STATUS_REJECTED))
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', DtrSubmission::STATUS_REJECTED)),
        ];
    }

    protected function getTabCount(?string $status): ?int
    {
        if (! SchemaFacade::hasTable('dtr_submissions')) {
            return null;
        }

        $query = DtrSubmission::query()
            ->where('submission_type', DtrSubmission::TYPE_PROOF);

        if ($status !== null) {
            $query->where('status', $status);
        }

        $count = $query->count();

        return $count > 0 ? $count : null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->modifyQueryWithActiveTab(
                DtrSubmission::query()
                    ->with(['payrollPeriod', 'branch', 'employee.branch', 'submittedByEmployee.branch', 'reviewedBy'])
                    ->where('submission_type', DtrSubmission::TYPE_PROOF)
                    ->latest()
            ))
            ->heading('On Field DTR Requests')
            ->description('Edit pending requests before approval. Approved and rejected requests are final and cannot be edited.')
            ->recordUrl(fn (DtrSubmission $record): string => ViewOnFieldDtrSubmission::getUrl([
                'submissionId' => $record->publicKey(),
            ]))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('employee_name_snapshot')
                    ->label('Employee')
                    ->getStateUsing(fn (DtrSubmission $record): string => $record->submittedEmployeeName())
                    ->weight(FontWeight::SemiBold)
                    ->description(function (DtrSubmission $record): string {
                        $parts = array_filter([
                            $record->employee_company_id_snapshot ?: null,
                            $record->submittedBranchName(),
                        ]);

                        return implode(' · ', $parts);
                    })
                    ->searchable(['employee_name_snapshot', 'employee_company_id_snapshot', 'branch_name_snapshot'])
                    ->sortable()
                    ->wrap(),

                TextColumn::make('attendance_summary')
                    ->label('Attendance')
                    ->getStateUsing(function (DtrSubmission $record): string {
                        $entries = $record->normalizedEntries();
                        $first = $entries[0] ?? null;

                        if (! $first) {
                            return '-';
                        }

                        $date = filled($first['date_in'] ?? null)
                            ? Carbon::parse($first['date_in'])->format('M d, Y')
                            : '-';
                        $extra = count($entries) > 1 ? ' (+'.(count($entries) - 1).')' : '';

                        return "{$date}{$extra}";
                    })
                    ->weight(FontWeight::Medium)
                    ->description(function (DtrSubmission $record): string {
                        $entries = $record->normalizedEntries();
                        $first = $entries[0] ?? null;

                        if (! $first) {
                            return '';
                        }

                        $in = filled($first['time_in'] ?? null)
                            ? Carbon::parse($first['time_in'])->format('g:i A')
                            : '-';
                        $out = filled($first['time_out'] ?? null)
                            ? Carbon::parse($first['time_out'])->format('g:i A')
                            : '-';

                        $overnight = '';
                        if (
                            filled($first['date_in'] ?? null)
                            && filled($first['date_out'] ?? null)
                            && $first['date_out'] !== $first['date_in']
                        ) {
                            $overnight = ' (+1d)';
                        }

                        $countSuffix = count($entries) > 1
                            ? ' · '.count($entries).' entries'
                            : '';

                        return "{$in} – {$out}{$overnight}{$countSuffix}";
                    })
                    ->tooltip(fn (DtrSubmission $record): string => $this->formatAttendanceTooltip($record))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('date_in', $direction))
                    ->wrap(),

                TextColumn::make('schedule_summary')
                    ->label('Schedule')
                    ->getStateUsing(function (DtrSubmission $record): string {
                        $entries = $record->normalizedEntries();
                        $first = $entries[0] ?? null;

                        if (! $first) {
                            return '-';
                        }

                        $defaults = app(OnFieldDtrService::class)->resolveEntryScheduleDefaults($record, $first);
                        $type = (string) ($defaults['schedule_type'] ?? 'Regular');

                        return static::onFieldScheduleOptions()[$type] ?? $type;
                    })
                    ->weight(FontWeight::Medium)
                    ->description(function (DtrSubmission $record): string {
                        $entries = $record->normalizedEntries();
                        $first = $entries[0] ?? null;

                        if (! $first) {
                            return '';
                        }

                        $defaults = app(OnFieldDtrService::class)->resolveEntryScheduleDefaults($record, $first);
                        $start = Carbon::parse($defaults['schedule_start'])->format('g:i A');
                        $end = Carbon::parse($defaults['schedule_end'])->format('g:i A');

                        return "{$start} – {$end}";
                    })
                    ->tooltip(fn (DtrSubmission $record): string => $this->formatAttendanceTooltip($record))
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('payrollPeriod.title')
                    ->label('Payroll Period')
                    ->weight(FontWeight::Medium)
                    ->description(fn (DtrSubmission $record): string => match (true) {
                        $record->payrollPeriod === null => '-',
                        (bool) $record->payrollPeriod->is_locked => 'Locked',
                        default => 'Open',
                    })
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->date('M d, Y')
                    ->weight(FontWeight::Medium)
                    ->description(fn (DtrSubmission $record): ?string => $record->created_at
                        ? $record->created_at->format('g:i A').' · '.$record->created_at->diffForHumans(short: true)
                        : null)
                    ->tooltip(fn (DtrSubmission $record): ?string => $record->created_at
                        ? $record->created_at->format('M d, Y g:i A').' ('.$record->created_at->diffForHumans().')'
                        : null)
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->icon(fn (string $state): Heroicon => match ($state) {
                        DtrSubmission::STATUS_APPROVED => Heroicon::CheckCircle,
                        DtrSubmission::STATUS_REJECTED => Heroicon::XCircle,
                        default => Heroicon::Clock,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        DtrSubmission::STATUS_APPROVED => 'success',
                        DtrSubmission::STATUS_REJECTED => 'danger',
                        default => 'warning',
                    })
                    ->description(function (DtrSubmission $record): string {
                        if ($record->reviewed_at) {
                            $by = $record->reviewedBy?->username ?: 'HR';

                            return "By {$by}";
                        }

                        return $record->viewed_at ? 'Viewed' : 'Unread';
                    })
                    ->tooltip(function (DtrSubmission $record): string {
                        if ($record->reviewed_at) {
                            $by = $record->reviewedBy?->username ?: 'HR';
                            $when = $record->reviewed_at->format('M d, Y g:i A');
                            $remarks = filled($record->reviewer_remarks) ? " — {$record->reviewer_remarks}" : '';

                            return "{$record->status} by {$by} on {$when}{$remarks}";
                        }

                        return $record->viewed_at
                            ? 'Opened by HR on '.$record->viewed_at->format('M d, Y g:i A')
                            : 'Not yet opened by HR';
                    })
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Remarks')
                    ->placeholder('None')
                    ->limit(50)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch / Station')
                    ->options(fn (): array => Branch::query()->orderBy('branch_name')->pluck('branch_name', 'id')->all())
                    ->searchable(),

                SelectFilter::make('payroll_period_id')
                    ->label('Payroll Period')
                    ->options(fn (): array => PayrollPeriod::query()->newestFirst()->pluck('title', 'id')->all())
                    ->searchable(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Review')
                    ->icon(Heroicon::Eye)
                    ->color('primary')
                    ->button()
                    ->size('xs')
                    ->url(fn (DtrSubmission $record): string => ViewOnFieldDtrSubmission::getUrl([
                        'submissionId' => $record->publicKey(),
                    ])),

                $this->makeEditOnFieldDtrAction('edit')
                    ->button()
                    ->outlined()
                    ->size('xs'),

                ActionGroup::make([
                    DeleteAction::make()
                        ->label('Delete')
                        ->visible(fn (DtrSubmission $record): bool => $record->isPending())
                        ->requiresConfirmation()
                        ->modalDescription('Only this pending request and its unreferenced proof file will be deleted.')
                        ->before(function (DtrSubmission $record): void {
                            if (
                                filled($record->file_path)
                                && Storage::disk('local')->exists($record->file_path)
                                && DtrSubmission::query()->whereKeyNot($record->getKey())->where('file_path', $record->file_path)->doesntExist()
                            ) {
                                Storage::disk('local')->delete($record->file_path);
                            }
                        }),
                ])
                    ->visible(fn (DtrSubmission $record): bool => $record->isPending())
                    ->icon(Heroicon::EllipsisVertical)
                    ->size('xs')
                    ->tooltip('More actions'),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(10)
            ->poll('15s');
    }

    protected function formatAttendanceTooltip(DtrSubmission $record): string
    {
        $entries = $record->normalizedEntries();

        if ($entries === []) {
            return 'No attendance entries.';
        }

        $service = app(OnFieldDtrService::class);
        $scheduleLabels = static::onFieldScheduleOptions();

        return collect($entries)
            ->values()
            ->map(function (array $entry, int $index) use ($record, $service, $scheduleLabels, $entries): string {
                $dateIn = filled($entry['date_in'] ?? null)
                    ? Carbon::parse($entry['date_in'])->format('M d, Y')
                    : '-';
                $in = filled($entry['time_in'] ?? null)
                    ? Carbon::parse($entry['time_in'])->format('g:i A')
                    : '-';
                $out = filled($entry['time_out'] ?? null)
                    ? Carbon::parse($entry['time_out'])->format('g:i A')
                    : '-';
                $defaults = $service->resolveEntryScheduleDefaults($record, $entry);
                $type = (string) ($defaults['schedule_type'] ?? 'Regular');
                $typeLabel = $scheduleLabels[$type] ?? $type;
                $schedStart = Carbon::parse($defaults['schedule_start'])->format('g:i A');
                $schedEnd = Carbon::parse($defaults['schedule_end'])->format('g:i A');
                $prefix = count($entries) > 1 ? 'Entry '.($index + 1).': ' : '';

                return "{$prefix}{$dateIn} ({$in} – {$out}) · {$typeLabel} ({$schedStart} – {$schedEnd})";
            })
            ->implode(' | ');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getTabsContentComponent(),
            EmbeddedTable::make(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->color('gray')
                ->url(Dtr::getUrl()),
        ];
    }
}
