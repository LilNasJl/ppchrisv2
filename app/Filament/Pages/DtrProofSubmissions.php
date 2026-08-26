<?php

namespace App\Filament\Pages;

use App\Models\DtrSubmission;
use BackedEnum;
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

class DtrProofSubmissions extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'On Field DTR';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentCheck;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && in_array($user->role, ['hr', 'admin'], true);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => DtrSubmission::query()
                ->with(['payrollPeriod', 'branch', 'employee', 'sicRcAccount'])
                ->where('submission_type', DtrSubmission::TYPE_PROOF)
                ->latest())
            ->heading('On Field DTR Requests')
            ->description('Review requests before they become official employee D.T.R records.')
            ->columns([
                TextColumn::make('index')->label('#')->rowIndex(),
                IconColumn::make('is_new')
                    ->label('New')
                    ->boolean()
                    ->trueIcon(Heroicon::BellAlert)
                    ->falseIcon(Heroicon::CheckCircle)
                    ->trueColor('danger')
                    ->falseColor('gray'),
                TextColumn::make('payrollPeriod.title')->label('Payroll Period')->searchable()->sortable()->wrap(),
                TextColumn::make('branch_name_snapshot')
                    ->label('Branch')
                    ->getStateUsing(fn (DtrSubmission $record): string => $record->submittedBranchName())
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('created_at')->label('Date Submitted')->dateTime('M d, Y h:i A')->sortable(),
                TextColumn::make('employee_name_snapshot')
                    ->label('Submitted By')
                    ->getStateUsing(fn (DtrSubmission $record): string => $record->submittedEmployeeName())
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        DtrSubmission::STATUS_APPROVED => 'success',
                        DtrSubmission::STATUS_REJECTED => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->icon(Heroicon::Eye)
                        ->url(fn (DtrSubmission $record): string => ViewOnFieldDtrSubmission::getUrl([
                            'submissionId' => $record->publicKey(),
                        ])),
                    DeleteAction::make()
                        ->label('Delete')
                        ->visible(fn (DtrSubmission $record): bool => $record->isPending())
                        ->requiresConfirmation()
                        ->modalDescription('Only this pending request and its proof file will be deleted.')
                        ->before(function (DtrSubmission $record): void {
                            if ($record->file_path && Storage::disk('local')->exists($record->file_path)) {
                                Storage::disk('local')->delete($record->file_path);
                            }
                        }),
                ])->icon(Heroicon::EllipsisHorizontal)->tooltip('Actions'),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(10)
            ->poll('15s');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
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
