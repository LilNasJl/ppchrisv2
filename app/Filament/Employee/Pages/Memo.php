<?php

namespace App\Filament\Employee\Pages;

use App\Models\Employee;
use App\Models\Memo as MemoModel;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class Memo extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.employee.pages.table-page';

    protected static ?string $slug = 'memo';

    protected static ?string $title = 'Notices';

    protected static ?string $navigationLabel = 'Notices';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Reports & Updates';

    protected static ?int $navigationSort = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => MemoModel::query()
                ->with('type')
                ->where('employee_id', $this->employee()->id)
                ->latest('created_at'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('created_at')
                    ->label('Date Filed')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Notice Title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('type.title')
                    ->label('Notice Type')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('attachment_name')
                    ->label('Attached File')
                    ->placeholder('No file')
                    ->wrap(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon(Heroicon::Eye)
                    ->modalSubmitAction(false)
                    ->modalHeading('Notice Details')
                    ->modalContent(fn (MemoModel $record) => view('filament.pages.partials.memo-details', [
                        'memo' => $record,
                    ])),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    protected function employee(): Employee
    {
        return auth()->user()->employee()->firstOrFail();
    }
}
