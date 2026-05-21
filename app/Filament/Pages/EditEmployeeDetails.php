<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ManagesEmployeeDetailsForm;
use App\Models\Employee as ModelsEmployee;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Override;

class EditEmployeeDetails extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;
    use ManagesEmployeeDetailsForm;

    protected string $view = 'filament.pages.employee-details-edit';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'employee-details/edit';

    protected static ?string $title = 'Edit Employee Details';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::PencilSquare;

    public ?array $data = [];

    public ?ModelsEmployee $employeeRecord = null;

    public function mount(): void
    {
        $this->employeeRecord = ModelsEmployee::query()
            ->with(['user', 'designation', 'department', 'branch', 'employeeDeductions.deduction'])
            ->findOrFail(request()->query('employeeId'));

        $this->form->fill($this->getEmployeeDetailsFormData($this->employeeRecord));
    }

    protected function getFormModel(): Model|string|null
    {
        return $this->employeeRecord;
    }

    protected function getFormStatePath(): ?string
    {
        return 'data';
    }

    protected function getFormSchema(): array
    {
        return $this->getEmployeeDetailsFormSchema();
    }

    public function save(): void
    {
        if (! $this->employeeRecord) {
            return;
        }

        $this->employeeRecord = $this->saveEmployeeDetails(
            $this->employeeRecord,
            $this->form->getState(),
        );

        $this->form->fill($this->getEmployeeDetailsFormData($this->employeeRecord));

        Notification::make()
            ->title('Employee details updated')
            ->success()
            ->send();
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label('View')
                ->icon(Heroicon::Eye)
                ->url(fn (): string => ViewEmployeeDetails::getUrl(['employeeId' => $this->employeeRecord?->id])),

            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(EmployeeDetails::getUrl()),
        ];
    }
}
