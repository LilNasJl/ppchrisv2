<?php

namespace App\Filament\Pages;

use App\Models\Branch as BranchModel;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;
use UnitEnum;

class ComplianceBenefits extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.compliance-benefits';

    protected static ?string $title = 'Deductions';

    protected static ?string $navigationLabel = 'Deductions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::MinusCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Employee Management';

    protected static ?int $navigationSort = 3;

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
            ? 'Deductions - '.$this->branch->branch_name
            : 'Deductions';
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
            Action::make('manageDeductions')
                ->label('Manage Deductions')
                ->icon(Heroicon::Cog6Tooth)
                ->url(ManageDeductions::getUrl());

        return $actions;
    }
}
