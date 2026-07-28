<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Branch as BranchModel;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;
use UnitEnum;

class EmployeeDetails extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.employee-details';

    protected static ?string $title = 'Accounts & Records';

    protected static ?string $navigationLabel = 'Masterdata';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Identification;

    protected static string|UnitEnum|null $navigationGroup = 'Employee Management';

    protected static ?int $navigationSort = 1;

    public ?int $branchId = null;

    public ?BranchModel $branch = null;

    public function mount(): void
    {
        $branchKey = request()->query('branchId');

        if (blank($branchKey)) {
            return;
        }

        $this->branchId = BranchModel::resolvePublicId($branchKey);
        abort_if(blank($this->branchId), 404);

        $this->branch = BranchModel::query()->findOrFail($this->branchId);
    }

    public function getTitle(): string
    {
        return $this->branch
            ? 'Accounts & Records - '.$this->branch->branch_name
            : 'Accounts & Records';
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        $actions = [];

        if ($this->branch) {
            $actions[] = Action::make('returnToBranches')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(static::getUrl());
        }

        $actions[] =
            ActionGroup::make([
                Action::make('importEmployee')
                    ->label('Import Employee')
                    ->icon(Heroicon::ArrowDownTray)
                    ->url(EmployeeImport::getUrl([
                        'returnUrl' => $this->recordsReturnUrl(),
                    ])),

                Action::make('importHistory')
                    ->label('Import History')
                    ->icon(Heroicon::QueueList)
                    ->url(UserResource::getUrl('import-history', [
                        'returnUrl' => $this->recordsReturnUrl(),
                    ])),

                Action::make('newEmployeeAccount')
                    ->label('New Employee Account')
                    ->icon(Heroicon::Plus)
                    ->url(UserResource::getUrl('create', [
                        'returnUrl' => $this->recordsReturnUrl(),
                    ])),
            ])
                ->label('Manage Records')
                ->icon(Heroicon::Cog6Tooth)
                ->button()
                ->tooltip('Manage Records');

        return $actions;
    }

    protected function recordsReturnUrl(): string
    {
        return $this->branch
            ? static::getUrl(['branchId' => $this->branch->publicKey()])
            : static::getUrl();
    }

    // protected ?string $subheading = 'Manage and view employee profiles and employement data';

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Action::make('add')
    //             ->label('Add')
    //     ];
    // }

}
