<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ManagesEmployeeDetailsForm;
use App\Models\Employee as ModelsEmployee;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Override;

class ViewEmployeeDetails extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;
    use ManagesEmployeeDetailsForm;

    protected string $view = 'filament.pages.employee-details-view';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'employee-details/view';

    protected static ?string $title = 'View Employee Details';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserCircle;

    public ?array $data = [];

    public ?ModelsEmployee $employeeRecord = null;

    public ?string $returnUrl = null;

    public function mount(): void
    {
        $this->returnUrl = $this->normalizeReturnUrl(request()->query('returnUrl'));

        $this->employeeRecord = ModelsEmployee::query()
            ->with(['user', 'designation', 'department', 'branch', 'employeeDeductions.deduction'])
            ->findOrFail(ModelsEmployee::resolvePublicId(request()->query('employeeId')));

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
        return $this->getEmployeeDetailsFormSchema(isReadOnly: true);
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            $this->employmentTypeHistoryAction(),

            Action::make('edit')
                ->label('Edit')
                ->icon(Heroicon::PencilSquare)
                ->url(fn (): string => EditEmployeeDetails::getUrl([
                    'employeeId' => $this->employeeRecord?->publicKey(),
                    'returnUrl' => $this->getReturnUrl(),
                ])),

            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(fn (): string => $this->getReturnUrl()),
        ];
    }

    protected function getReturnUrl(): string
    {
        return $this->returnUrl ?: EmployeeDetails::getUrl();
    }

    protected function normalizeReturnUrl(mixed $url): ?string
    {
        if (! is_string($url) || blank($url)) {
            return null;
        }

        $appUrl = url('/');

        if (str_starts_with($url, $appUrl) || str_starts_with($url, '/')) {
            return $url;
        }

        return null;
    }
}
