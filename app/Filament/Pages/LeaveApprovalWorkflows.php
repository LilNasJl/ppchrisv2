<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Leaves\LeaveResource;
use App\Filament\Support\LeaveReviewActions;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\LeaveApprovalWorkflow;
use App\Services\LeaveApprovalAccess;
use App\Services\LeaveApprovalService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class LeaveApprovalWorkflows extends Page implements HasTable
{
    use HasPageShield {
        canAccess as protected canAccessWithShield;
    }
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static ?string $title = 'Leave Approval Workflows';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::AdjustmentsHorizontal;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 8;

    public static function canAccess(): bool
    {
        return static::canAccessWithShield() && LeaveApprovalAccess::configure(auth()->user());
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        return $table->query(LeaveApprovalWorkflow::with('levels'))->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Workflow')->searchable()->weight('semibold')->description(fn ($record) => 'Revision '.$record->version),
                TextColumn::make('scope')->state(fn ($record) => $record->scopeLabel())->wrap(),
                TextColumn::make('route')->label('Approval Route')->state(fn ($record) => $record->levels->pluck('label')->push('HR')->join(' > '))->wrap(),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('updated_at')->label('Updated')->dateTime('M d, Y h:i A'),
            ])->recordActions([
                Action::make('edit')->label('Edit Workflow')->icon(Heroicon::PencilSquare)->schema($this->formFields())
                    ->fillForm(function (LeaveApprovalWorkflow $record): array {
                        $scope = collect(['employee', 'designation', 'branch', 'department'])
                            ->first(fn (string $type): bool => filled($record->{$type.'_id'}));

                        return [
                            'name' => $record->name,
                            'is_active' => $record->is_active,
                            'version' => $record->version,
                            'scope_type' => $scope ?: 'company',
                            'scope_id' => $scope ? $record->{$scope.'_id'} : null,
                            'levels' => $record->levels->map(fn ($level): array => [
                                'label' => $level->label,
                                'approver_employee_id' => $level->approver_employee_id,
                            ])->all(),
                        ];
                    })
                    ->modalWidth(Width::FourExtraLarge)->modalSubmitActionLabel('Save Workflow')
                    ->action(fn ($record, array $data) => app(LeaveApprovalService::class)->saveWorkflow($data, auth()->user(), $record)),
                Action::make('revisions')->label('History')->icon(Heroicon::Clock)->modalSubmitAction(false)
                    ->modalContent(fn ($record) => view('filament.leave.workflow-revisions', ['revisions' => DB::table('leave_workflow_revisions')->where('workflow_id', $record->id)->orderByDesc('version')->get()])),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')->label('New Workflow')->icon(Heroicon::Plus)->schema($this->formFields())
                ->modalWidth(Width::FourExtraLarge)->modalSubmitActionLabel('Create Workflow')
                ->action(fn (array $data) => app(LeaveApprovalService::class)->saveWorkflow($data, auth()->user())),
            Action::make('preview')->label('Check Employee Route')->icon(Heroicon::MagnifyingGlass)->color('gray')
                ->modalSubmitAction(false)->schema([
                    Select::make('employee_id')->label('Employee')->options(fn () => Employee::activeEmployment()->orderBy('lastname')->get()->mapWithKeys(fn ($e) => [$e->id => $e->full_name]))->searchable()->live(),
                    View::make('filament.leave.workflow-preview')->viewData(fn (Get $get) => ['employee' => Employee::find($get('employee_id'))]),
                ]),
            Action::make('leaveTracking')->label('Leave Tracking')->icon(Heroicon::ArrowLeft)->color('gray')->url(LeaveResource::getUrl()),
        ];
    }

    private function formFields(): array
    {
        return [
            Hidden::make('version'),
            Section::make('Workflow')->schema([
                TextInput::make('name')->label('Workflow Name')->required()->maxLength(150),
                Toggle::make('is_active')->label('Active')->default(true)->required(),
            ])->columns(2),
            Section::make('Applies To')->schema([
                Select::make('scope_type')->label('Apply Workflow To')->options([
                    'company' => 'Company-wide',
                    'employee' => 'Employee',
                    'designation' => 'Designation',
                    'branch' => 'Branch / Station',
                    'department' => 'Department',
                ])->default('company')->required()->live()
                    ->afterStateUpdated(fn (Set $set) => $set('scope_id', null)),
                Select::make('scope_id')->label(fn (Get $get): string => match ($get('scope_type')) {
                    'employee' => 'Employee',
                    'designation' => 'Designation',
                    'branch' => 'Branch / Station',
                    'department' => 'Department',
                    default => 'Selection',
                })->options(fn (Get $get): array => match ($get('scope_type')) {
                    'employee' => Employee::activeEmployment()->orderBy('lastname')->get()->mapWithKeys(fn (Employee $employee): array => [$employee->id => $employee->full_name])->all(),
                    'designation' => Designation::orderBy('title')->pluck('title', 'id')->all(),
                    'branch' => Branch::orderBy('branch_name')->pluck('branch_name', 'id')->all(),
                    'department' => Department::orderBy('name')->pluck('name', 'id')->all(),
                    default => [],
                })->visible(fn (Get $get): bool => in_array($get('scope_type'), ['employee', 'designation', 'branch', 'department'], true))
                    ->required(fn (Get $get): bool => $get('scope_type') !== 'company')
                    ->searchable()->preload(),
            ])->columns(2),
            Repeater::make('levels')->label('Preliminary Approval Levels')->defaultItems(0)->maxItems(20)
                ->addActionLabel('Add Approval Level')->reorderableWithButtons()->collapsible()
                ->itemLabel(fn (array $state) => $state['label'] ?? 'Approval Level')->schema([
                    TextInput::make('label')->label('Level Name')->placeholder('Department Head')->required()->maxLength(100),
                    Select::make('approver_employee_id')->label('Approver')->required()->searchable()->options(fn () => LeaveReviewActions::employeeOptions()),
                ])->columns(2),
            View::make('filament.leave.final-step'),
        ];
    }
}
