<?php

namespace App\Filament\Pages\Concerns;

use App\Models\DtrSubmission;
use App\Models\PayrollPeriod;
use App\Services\DtrChangeRequestService;
use App\Services\OnFieldDtrService;
use Carbon\Carbon;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

trait InteractsWithOnFieldDtrEdit
{
    /**
     * @return array<string, string>
     */
    public static function onFieldScheduleOptions(): array
    {
        return [
            'Regular' => 'Regular',
            'Flexible' => 'Flexible',
            'Shift1' => 'Shift 1',
            'Shift2' => 'Shift 2',
            'Shift3' => 'Shift 3',
            'Brkn1' => 'Broken Shift 1',
            'Brkn2' => 'Broken Shift 2',
            'Brkn3' => 'Broken Shift 3',
            'Saturday' => 'Saturday',
            'Overtime' => 'Overtime Only',
        ];
    }

    protected function makeEditOnFieldDtrAction(
        string $name = 'edit',
        ?Closure $resolveSubmission = null,
        ?Closure $afterSave = null,
    ): Action {
        $findSubmission = static function (?DtrSubmission $record = null) use ($resolveSubmission): ?DtrSubmission {
            if ($record instanceof DtrSubmission) {
                return $record;
            }

            return $resolveSubmission ? $resolveSubmission() : null;
        };

        return Action::make($name)
            ->label('Edit')
            ->icon(Heroicon::PencilSquare)
            ->color('warning')
            ->visible(function (?DtrSubmission $record = null) use ($findSubmission): bool {
                $submission = $findSubmission($record);

                return $submission instanceof DtrSubmission
                    && app(OnFieldDtrService::class)->canHrEdit($submission);
            })
            ->disabled(function (?DtrSubmission $record = null) use ($findSubmission): bool {
                $submission = $findSubmission($record);

                return ! ($submission instanceof DtrSubmission)
                    || ! app(OnFieldDtrService::class)->canHrEdit($submission);
            })
            ->tooltip(function (?DtrSubmission $record = null) use ($findSubmission): ?string {
                $submission = $findSubmission($record);

                if ($submission instanceof DtrSubmission && ! app(OnFieldDtrService::class)->canHrEdit($submission)) {
                    return 'Approved or rejected On Field D.T.R requests cannot be edited.';
                }

                return 'Edit dates, punches, schedules, period, proof, or remarks';
            })
            ->modalWidth('4xl')
            ->modalHeading(function (?DtrSubmission $record = null) use ($findSubmission): string {
                $submission = $findSubmission($record);

                return $submission instanceof DtrSubmission
                    ? 'Edit On Field DTR — '.$submission->submittedEmployeeName()
                    : 'Edit On Field DTR';
            })
            ->modalDescription(function (?DtrSubmission $record = null) use ($findSubmission): string {
                $submission = $findSubmission($record);

                if (! $submission instanceof DtrSubmission) {
                    return '';
                }

                return 'Adjust attendance entries, schedule details, payroll period, or proof file before approval.';
            })
            ->modalSubmitActionLabel('Save Changes')
            ->fillForm(function (?DtrSubmission $record = null) use ($findSubmission): array {
                $submission = $findSubmission($record);

                if (! $submission instanceof DtrSubmission) {
                    return [];
                }

                $submission->loadMissing(['employee.branch', 'branch', 'payrollPeriod']);
                $service = app(OnFieldDtrService::class);
                $periodIsLocked = (bool) $submission->payrollPeriod?->is_locked;

                $entries = array_map(function (array $entry) use ($service, $submission): array {
                    $defaults = $service->resolveEntryScheduleDefaults($submission, $entry);

                    return [
                        'date_in' => $entry['date_in'] ?: null,
                        'time_in' => static::formatTimeForInput($entry['time_in'] ?? null),
                        'date_out' => $entry['date_out'] ?: ($entry['date_in'] ?: null),
                        'time_out' => static::formatTimeForInput($entry['time_out'] ?? null),
                        'schedule_type' => $defaults['schedule_type'],
                        'schedule_start' => static::formatTimeForInput($defaults['schedule_start']),
                        'schedule_end' => static::formatTimeForInput($defaults['schedule_end']),
                    ];
                }, $submission->normalizedEntries());

                return [
                    'payroll_period_id' => $periodIsLocked ? null : (int) $submission->payroll_period_id,
                    'entries' => $entries,
                    'description' => $submission->description,
                    'reviewer_remarks' => $submission->reviewer_remarks,
                    'proof_file' => [],
                ];
            })
            ->schema([
                Select::make('payroll_period_id')
                    ->label('Payroll Period')
                    ->options(fn (): array => PayrollPeriod::query()
                        ->where('is_locked', false)
                        ->newestFirst()
                        ->pluck('title', 'id')
                        ->all())
                    ->helperText(function (?DtrSubmission $record = null) use ($findSubmission): ?string {
                        $submission = $findSubmission($record);

                        return $submission?->payrollPeriod?->is_locked
                            ? "Previously submitted under locked period ({$submission->payrollPeriod->title}). Select an open payroll period to apply changes."
                            : null;
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull(),

                Repeater::make('entries')
                    ->label('D.T.R Attendance Entries')
                    ->addActionLabel('Add D.T.R Entry')
                    ->minItems(1)
                    ->collapsible()
                    ->itemLabel(function (array $state): string {
                        if (blank($state['date_in'] ?? null)) {
                            return 'D.T.R Entry';
                        }

                        try {
                            $date = Carbon::parse($state['date_in'])->format('M d, Y');
                        } catch (\Throwable) {
                            $date = (string) $state['date_in'];
                        }

                        $sched = filled($state['schedule_type'] ?? null) ? " [{$state['schedule_type']}]" : '';
                        $in = static::formatTimeLabel($state['time_in'] ?? null);
                        $out = static::formatTimeLabel($state['time_out'] ?? null);

                        return "Entry: {$date}{$sched} ({$in} - {$out})";
                    })
                    ->schema([
                        DatePicker::make('date_in')
                            ->label('Date In')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                                if (blank($state)) {
                                    return;
                                }

                                $start = $get('schedule_start');
                                $end = $get('schedule_end');

                                try {
                                    if (filled($start) && filled($end) && Carbon::parse($end)->lessThanOrEqualTo(Carbon::parse($start))) {
                                        $set('date_out', Carbon::parse($state)->addDay()->toDateString());

                                        return;
                                    }
                                } catch (\Throwable) {
                                }

                                $set('date_out', Carbon::parse($state)->toDateString());
                            }),

                        TimePicker::make('time_in')
                            ->label('Time In')
                            ->seconds(false)
                            ->required(),

                        DatePicker::make('date_out')
                            ->label('Date Out')
                            ->required(),

                        TimePicker::make('time_out')
                            ->label('Time Out')
                            ->seconds(false)
                            ->required(),

                        Select::make('schedule_type')
                            ->label('Sched Type')
                            ->options(static::onFieldScheduleOptions())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set, Get $get, ?DtrSubmission $record = null) use ($findSubmission): void {
                                if (blank($state)) {
                                    return;
                                }

                                $submission = $findSubmission($record);
                                $branch = $submission?->employee?->branch ?: $submission?->branch;
                                [$start, $end] = app(DtrChangeRequestService::class)
                                    ->resolveBranchScheduleTimes($branch, (string) $state, formatForInput: false);

                                if (filled($start)) {
                                    $set('schedule_start', static::formatTimeForInput($start));
                                }

                                if (filled($end)) {
                                    $set('schedule_end', static::formatTimeForInput($end));
                                }

                                $dateIn = $get('date_in');
                                $schedStart = $start ?: $get('schedule_start');
                                $schedEnd = $end ?: $get('schedule_end');

                                if (filled($dateIn) && filled($schedStart) && filled($schedEnd)) {
                                    try {
                                        if (Carbon::parse($schedEnd)->lessThanOrEqualTo(Carbon::parse($schedStart))) {
                                            $set('date_out', Carbon::parse($dateIn)->addDay()->toDateString());
                                        } else {
                                            $set('date_out', Carbon::parse($dateIn)->toDateString());
                                        }
                                    } catch (\Throwable) {
                                    }
                                }
                            }),

                        TimePicker::make('schedule_start')
                            ->label('Sched Start')
                            ->seconds(false)
                            ->required(),

                        TimePicker::make('schedule_end')
                            ->label('Sched End')
                            ->seconds(false)
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('Description / D.T.R Comment')
                    ->rows(3)
                    ->maxLength(2000)
                    ->columnSpanFull(),

                Textarea::make('reviewer_remarks')
                    ->label('HR Remarks (Optional)')
                    ->placeholder('Document adjustments or review notes...')
                    ->rows(2)
                    ->maxLength(2000)
                    ->columnSpanFull(),

                FileUpload::make('proof_file')
                    ->label('Replace Proof File (Optional)')
                    ->disk('local')
                    ->directory('dtr-proof-submissions')
                    ->visibility('private')
                    ->acceptedFileTypes(['application/pdf', 'image/png', 'image/jpeg'])
                    ->maxSize(20480)
                    ->helperText(function (?DtrSubmission $record = null) use ($findSubmission): string {
                        $submission = $findSubmission($record);

                        return filled($submission?->file_name)
                            ? "Current file: {$submission->file_name}. Leave empty to keep the existing proof."
                            : 'Upload a PDF, PNG, or JPG supporting document.';
                    })
                    ->columnSpanFull(),
            ])
            ->extraModalFooterActions(function (Action $action, ?DtrSubmission $record = null) use ($findSubmission): array {
                $submission = $findSubmission($record);

                if (! $submission instanceof DtrSubmission || ! $submission->isPending()) {
                    return [];
                }

                return [
                    $action->makeModalSubmitAction('saveAndApprove', arguments: ['approve' => true])
                        ->label('Save & Approve')
                        ->icon(Heroicon::CheckCircle)
                        ->color('success'),
                ];
            })
            ->action(function (array $data, array $arguments, Action $action, ?DtrSubmission $record = null) use ($findSubmission, $afterSave): mixed {
                $submission = $findSubmission($record);
                $user = auth()->user();

                if (! $submission instanceof DtrSubmission || ! $user) {
                    Notification::make()
                        ->title('On Field DTR was not found')
                        ->danger()
                        ->send();

                    return null;
                }

                $service = app(OnFieldDtrService::class);
                $shouldApprove = (bool) ($arguments['approve'] ?? false);

                try {
                    if ($shouldApprove && $submission->isPending()) {
                        $updated = DB::transaction(function () use ($service, $submission, $user, $data): DtrSubmission {
                            $saved = $service->updateByHr($submission, $user, $data);

                            return $service->approve($saved, $user, $data['reviewer_remarks'] ?? null);
                        }, 3);

                        Notification::make()
                            ->title('On Field DTR updated & approved')
                            ->body('Changes were saved and linked official and employee-visible D.T.R records were created.')
                            ->success()
                            ->send();
                    } else {
                        $updated = $service->updateByHr($submission, $user, $data);

                        Notification::make()
                            ->title('On Field DTR updated')
                            ->body('The pending On Field D.T.R request details were updated.')
                            ->success()
                            ->send();
                    }
                } catch (\DomainException $exception) {
                    Notification::make()
                        ->title('Unable to update On Field DTR')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    $action->halt();

                    return null;
                } catch (ValidationException $exception) {
                    $message = collect($exception->errors())->flatten()->first() ?? $exception->getMessage();

                    Notification::make()
                        ->title('Validation error')
                        ->body((string) $message)
                        ->danger()
                        ->send();

                    $action->halt();

                    return null;
                }

                if ($afterSave !== null) {
                    return $afterSave($updated);
                }

                return null;
            });
    }

    protected static function formatTimeForInput(?string $time): ?string
    {
        if (blank($time)) {
            return null;
        }

        try {
            return Carbon::parse($time)->format('H:i');
        } catch (\Throwable) {
            return $time;
        }
    }

    protected static function formatTimeLabel(?string $time): string
    {
        if (blank($time)) {
            return '-';
        }

        try {
            return Carbon::parse($time)->format('h:i A');
        } catch (\Throwable) {
            return (string) $time;
        }
    }
}
