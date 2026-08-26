<?php

namespace App\Filament\Pages;

use App\Models\DtrSubmission;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class DtrSubmissions extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'DTR Submissions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowDownTray;

    public function mount(): void
    {
        DtrSubmission::query()
            ->where('submission_type', DtrSubmission::TYPE_DTR)
            ->where('is_new', true)
            ->update(['is_new' => false, 'viewed_at' => now()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => DtrSubmission::query()
                ->with(['payrollPeriod', 'branch', 'sicRcAccount'])
                ->where('submission_type', DtrSubmission::TYPE_DTR)
                ->latest())
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                IconColumn::make('is_new')
                    ->label('New')
                    ->boolean()
                    ->trueIcon(Heroicon::BellAlert)
                    ->falseIcon(Heroicon::CheckCircle)
                    ->trueColor('danger')
                    ->falseColor('gray'),

                TextColumn::make('payrollPeriod.title')
                    ->label('Payroll Period')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('branch.branch_name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Date Submitted')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),

                TextColumn::make('sicRcAccount.username')
                    ->label('Submitted By')
                    ->placeholder('Unknown')
                    ->searchable(),

                TextColumn::make('comments')
                    ->label('Comments')
                    ->placeholder('None')
                    ->limit(80)
                    ->wrap()
                    ->toggleable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('download')
                        ->label('Download')
                        ->icon(Heroicon::ArrowDownTray)
                        ->url(fn (DtrSubmission $record): string => route('hr_tools.dtr_submissions.download', $record))
                        ->openUrlInNewTab(),

                    DeleteAction::make()
                        ->before(function (DtrSubmission $record): void {
                            if ($record->file_path && Storage::disk('local')->exists($record->file_path)) {
                                Storage::disk('local')->delete($record->file_path);
                            }
                        }),
                ])
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->tooltip('Actions'),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(10);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(Dtr::getUrl()),
        ];
    }
}
