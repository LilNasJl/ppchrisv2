<?php

namespace App\Filament\Resources\Deductions;

use App\Filament\Resources\Deductions\Pages\CreateDeduction;
use App\Filament\Resources\Deductions\Pages\EditDeduction;
use App\Filament\Resources\Deductions\Pages\ListDeductions;
use App\Filament\Resources\Deductions\Schemas\DeductionForm;
use App\Filament\Resources\Deductions\Tables\DeductionsTable;
use App\Models\Deduction as ModelsDeduction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class DeductionResource extends Resource
{
    protected static ?string $model = ModelsDeduction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::MinusCircle;

    protected static ?string $recordTitleAttribute = 'Deduction';

    protected static string|UnitEnum|null $navigationGroup = 'Employee Management';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return DeductionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeductionsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotIn('title', ModelsDeduction::defaultTitles());
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeductions::route('/'),
            'create' => CreateDeduction::route('/create'),
            'edit' => EditDeduction::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
