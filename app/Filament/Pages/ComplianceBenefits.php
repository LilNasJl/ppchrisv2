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

    protected static ?string $title = 'Benefits & Company Deduction Management';

    protected static ?string $navigationLabel = 'Benefits & Company Deduction Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::MinusCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Compensation and Benefits Management';

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
            ? 'Benefits & Company Deduction Management - '.$this->branch->branch_name
            : 'Benefits & Company Deduction Management';
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
