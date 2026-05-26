<?php

namespace App\Filament\Pages;

use App\Models\Deduction;
use App\Models\Employee;
use App\Models\EmployeeDeduction;
use App\Services\EmployeeDeductionService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Override;

class EmployeeComplianceBenefits extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected string $view = 'filament.pages.employee-compliance-benefits';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Employee Deductions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShieldCheck;

    public ?int $employeeId = null;

    public ?Employee $employee = null;

    public ?array $data = [];

    public function mount(): void
    {
        $this->employeeId = Employee::resolvePublicId(request()->query('employeeId'));
        $this->employee = Employee::query()
            ->with(['employeeDeductions.deduction', 'branch', 'designation'])
            ->findOrFail($this->employeeId);

        app(EmployeeDeductionService::class)->ensureDefaultDeductions($this->employee);

        $this->form->fill($this->deductionFormData());
    }

    protected function getFormStatePath(): ?string
    {
        return 'data';
    }

    protected function getFormSchema(): array
    {
        return [
            Placeholder::make('employee_summary')
                ->hiddenLabel()
                ->content(fn (): HtmlString => new HtmlString(
                    '<div style="display:grid;gap:4px;">'
                    .'<div style="font-weight:700;">'.e($this->employee?->company_id.' - '.$this->employee?->full_name).'</div>'
                    .'<div style="color:#64748b;font-size:13px;">'.e(($this->employee?->designation?->title ?: 'No designation').' | '.($this->employee?->branch?->branch_name ?: 'No branch')).'</div>'
                    .'</div>'
                ))
                ->columnSpanFull(),

            Fieldset::make('Company Deductions')
                ->schema(fn (): array => $this->employeeDeductionFields(Deduction::CATEGORY_COMPANY))
                ->columns(1),

            Fieldset::make('Remittances')
                ->schema(fn (): array => $this->employeeDeductionFields(Deduction::CATEGORY_REMITTANCE))
                ->columns(1),

            Fieldset::make('Other Deductions')
                ->schema(fn (Get $get): array => [
                    Select::make('other_deduction_ids')
                        ->label('Linked Other Deductions')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->options(fn (): array => $this->deductionOptions(Deduction::CATEGORY_OTHER))
                        ->helperText('Only selected other deductions are linked to this employee.')
                        ->columnSpanFull(),

                    ...$this->employeeDeductionFields(Deduction::CATEGORY_OTHER, $get('other_deduction_ids') ?? []),
                ])
                ->columns(1),
        ];
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(ComplianceBenefits::getUrl()),

            Action::make('save')
                ->label('Save Deductions')
                ->icon(Heroicon::Check)
                ->action('save'),
        ];
    }

    public function save(): void
    {
        if (! $this->employee) {
            return;
        }

        $state = $this->form->getState();
        $selectedOtherIds = collect($state['other_deduction_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $deductionIds = Deduction::query()
            ->whereIn('category', [Deduction::CATEGORY_COMPANY, Deduction::CATEGORY_REMITTANCE])
            ->pluck('id')
            ->map(fn (int $id): int => $id)
            ->merge($selectedOtherIds)
            ->unique()
            ->values();

        DB::transaction(function () use ($state, $deductionIds, $selectedOtherIds): void {
            EmployeeDeduction::query()
                ->where('employee_id', $this->employee->id)
                ->whereHas('deduction', fn (Builder $query) => $query->where('category', Deduction::CATEGORY_OTHER))
                ->whereNotIn('deduction_id', $selectedOtherIds)
                ->update([
                    'active' => false,
                    'completed_at' => now(),
                ]);

            Deduction::query()
                ->whereIn('id', $deductionIds)
                ->get()
                ->each(function (Deduction $deduction) use ($state): void {
                    $deductionState = $state['deductions'][$deduction->id] ?? [];
                    $termType = $deductionState['term_type'] ?? $deduction->term_type ?: Deduction::TERM_PERMANENT;
                    $termPeriods = $termType === Deduction::TERM_FIXED
                        ? max(1, (int) ($deductionState['term_periods'] ?? $deduction->term_periods ?? 1))
                        : null;
                    $remainingTerms = $termType === Deduction::TERM_FIXED
                        ? max(0, (int) ($deductionState['remaining_terms'] ?? $termPeriods))
                        : null;

                    EmployeeDeduction::updateOrCreate(
                        [
                            'employee_id' => $this->employee->id,
                            'deduction_id' => $deduction->id,
                        ],
                        [
                            'amount' => $deductionState['amount'] ?? 0,
                            'term_type' => $termType,
                            'term_periods' => $termPeriods,
                            'remaining_terms' => $remainingTerms,
                            'active' => $termType === Deduction::TERM_PERMANENT || $remainingTerms > 0,
                            'completed_at' => $termType === Deduction::TERM_FIXED && $remainingTerms <= 0 ? now() : null,
                        ],
                    );
                });
        });

        $this->employee->refresh();
        $this->form->fill($this->deductionFormData());

        Notification::make()
            ->title('Employee deductions updated')
            ->success()
            ->send();
    }

    protected function deductionFormData(): array
    {
        $employeeDeductions = $this->employee
            ? $this->employee->employeeDeductions()->with('deduction')->get()->keyBy('deduction_id')
            : collect();

        $deductions = Deduction::query()->get()->mapWithKeys(function (Deduction $deduction) use ($employeeDeductions): array {
            $employeeDeduction = $employeeDeductions->get($deduction->id);
            $termType = $employeeDeduction?->term_type ?: $deduction->term_type ?: Deduction::TERM_PERMANENT;
            $termPeriods = $employeeDeduction?->term_periods ?? $deduction->term_periods;

            return [
                $deduction->id => [
                    'amount' => $employeeDeduction?->amount ?? 0,
                    'term_type' => $termType,
                    'term_periods' => $termType === Deduction::TERM_FIXED ? ($termPeriods ?: 1) : null,
                    'remaining_terms' => $employeeDeduction?->remaining_terms ?? ($termType === Deduction::TERM_FIXED ? ($termPeriods ?: 1) : null),
                ],
            ];
        })->all();

        return [
            'deductions' => $deductions,
            'other_deduction_ids' => $employeeDeductions
                ->filter(fn (EmployeeDeduction $employeeDeduction): bool => $employeeDeduction->active && $employeeDeduction->deduction?->category === Deduction::CATEGORY_OTHER)
                ->keys()
                ->map(fn ($id): string => (string) $id)
                ->values()
                ->all(),
        ];
    }

    protected function employeeDeductionFields(string $category, array $selectedIds = []): array
    {
        $deductions = Deduction::query()
            ->where('category', $category)
            ->when($category === Deduction::CATEGORY_OTHER, fn (Builder $query) => $query->whereIn('id', $selectedIds ?: [0]))
            ->orderBy('title')
            ->get();

        if ($deductions->isEmpty()) {
            return [
                Placeholder::make("empty_{$category}_deductions")
                    ->hiddenLabel()
                    ->content('No deductions available.')
                    ->columnSpanFull(),
            ];
        }

        return $deductions
            ->map(fn (Deduction $deduction): Group => Group::make([
                Placeholder::make("deduction_{$deduction->id}_details")
                    ->hiddenLabel()
                    ->content(fn (): HtmlString => new HtmlString(
                        '<div style="font-weight:700;">'.e($deduction->title).'</div>'
                        .'<div style="color:#64748b;font-size:12px;">'.e($deduction->description ?: 'No description').'</div>'
                    )),

                TextInput::make("deductions.{$deduction->id}.amount")
                    ->label('Amount')
                    ->numeric()
                    ->prefix('₱')
                    ->default(0),

                Select::make("deductions.{$deduction->id}.term_type")
                    ->label('Term')
                    ->options(Deduction::termTypeOptions())
                    ->default($deduction->term_type ?: Deduction::TERM_PERMANENT)
                    ->live()
                    ->required(),

                TextInput::make("deductions.{$deduction->id}.term_periods")
                    ->label('Total Terms')
                    ->numeric()
                    ->minValue(1)
                    ->visible(fn (Get $get): bool => $get("deductions.{$deduction->id}.term_type") === Deduction::TERM_FIXED),

                TextInput::make("deductions.{$deduction->id}.remaining_terms")
                    ->label('Remaining Terms')
                    ->numeric()
                    ->minValue(0)
                    ->visible(fn (Get $get): bool => $get("deductions.{$deduction->id}.term_type") === Deduction::TERM_FIXED),
            ])->columns([
                'default' => 1,
                'md' => 5,
            ]))
            ->all();
    }

    protected function deductionOptions(string $category): array
    {
        return Deduction::query()
            ->where('category', $category)
            ->orderBy('title')
            ->pluck('title', 'id')
            ->all();
    }
}
