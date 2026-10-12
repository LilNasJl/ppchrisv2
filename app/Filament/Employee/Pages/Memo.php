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

    protected string $view = 'filament.employee.pages.memo';

    protected static ?string $slug = 'memo';

    protected static ?string $title = 'Notices';

    protected static ?string $navigationLabel = 'Notices';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BellAlert;

    protected static string|\UnitEnum|null $navigationGroup = 'Reports & Updates';

    protected static ?int $navigationSort = 1;

    public function getSubheading(): ?string
    {
        return 'Review official company circulars, policy announcements, and notices issued to you.';
    }

    public function getMemoStats(): array
    {
        $employee = $this->employee();
        $total = MemoModel::query()->where('employee_id', $employee->id)->count();
        $thisMonth = MemoModel::query()
            ->where('employee_id', $employee->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        $withAttachment = MemoModel::query()
            ->where('employee_id', $employee->id)
            ->whereNotNull('attachment_path')
            ->where('attachment_path', '!=', '')
            ->count();

        return [
            'total' => $total,
            'this_month' => $thisMonth,
            'with_attachment' => $withAttachment,
        ];
    }

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
                    ->icon('heroicon-m-calendar')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Notice Title')
                    ->icon('heroicon-m-megaphone')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('type.title')
                    ->label('Notice Type')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-m-tag')
                    ->placeholder('General Notice')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('attachment_name')
                    ->label('Attached File')
                    ->icon(fn ($record) => filled($record->attachment_path) ? 'heroicon-m-paper-clip' : null)
                    ->placeholder('None')
                    ->wrap(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon(Heroicon::Eye)
                    ->color('primary')
                    ->modalSubmitAction(false)
                    ->modalHeading('Official Notice Details')
                    ->modalContent(fn (MemoModel $record) => view('filament.pages.partials.memo-details', [
                        'memo' => $record,
                    ])),
            ])
            ->emptyStateHeading('No notices found')
            ->emptyStateDescription('There are currently no official notices, circulars, or memos issued for your account.')
            ->emptyStateIcon(Heroicon::BellSlash);
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
