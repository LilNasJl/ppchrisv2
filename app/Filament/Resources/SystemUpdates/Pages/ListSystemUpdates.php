<?php

namespace App\Filament\Resources\SystemUpdates\Pages;

use App\Filament\Resources\SystemUpdates\SystemUpdateResource;
use App\Models\SystemUpdate;
use App\Models\SystemUpdateAcknowledgment;
use App\Models\SystemUpdateSetting;
use App\Models\User;
use App\Services\SystemUpdateService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rule;

class ListSystemUpdates extends ListRecords
{
    protected static string $resource = SystemUpdateResource::class;

    protected string $view = 'filament.resources.system-updates.pages.list-system-updates';

    public string $popupFrequency = SystemUpdateSetting::FREQUENCY_UNTIL_ACKNOWLEDGED;

    public bool $showTopbarButton = true;

    public string $modalHeading = 'System Updates & Notes';

    public string $modalSubtitle = '';

    public function mount(): void
    {
        parent::mount();

        $this->loadPopupSettings();
    }

    public function loadPopupSettings(): void
    {
        $setting = SystemUpdateSetting::current();

        $this->popupFrequency = (string) $setting->popup_frequency;
        $this->showTopbarButton = (bool) $setting->show_topbar_button;
        $this->modalHeading = (string) ($setting->modal_heading ?: 'System Updates & Notes');
        $this->modalSubtitle = (string) ($setting->modal_subtitle ?? '');
    }

    public function savePopupSettings(): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User && $user->can('Update:SystemUpdate'), 403);

        $validated = $this->validate([
            'popupFrequency' => ['required', Rule::in(array_keys(SystemUpdateSetting::frequencyOptions()))],
            'showTopbarButton' => ['boolean'],
            'modalHeading' => ['required', 'string', 'max:120'],
            'modalSubtitle' => ['nullable', 'string', 'max:255'],
        ]);

        $setting = SystemUpdateSetting::current();
        $setting->fill([
            'popup_frequency' => $validated['popupFrequency'],
            'show_topbar_button' => (bool) $validated['showTopbarButton'],
            'modal_heading' => trim($validated['modalHeading']),
            'modal_subtitle' => filled($validated['modalSubtitle']) ? trim((string) $validated['modalSubtitle']) : null,
            'updated_by' => $user->id,
        ]);
        $setting->save();

        $this->dispatch('system-updates-acknowledged');

        Notification::make()
            ->success()
            ->title('Popup frequency and display settings saved')
            ->send();
    }

    public function getSubheading(): string
    {
        return 'Configure how often the login popup appears and manage Updates & Notes for HR and Employee portals.';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('previewHrPopup')
                    ->label('Preview HR Popup')
                    ->icon(Heroicon::BuildingOffice2)
                    ->color('info')
                    ->modalHeading(fn (): string => $this->modalHeading ?: 'System Updates & Notes (HR Preview)')
                    ->modalDescription('Preview of Updates & Notes visible to HR & Admin accounts.')
                    ->modalWidth('2xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close Preview')
                    ->modalContent(fn () => view('filament.components.system-updates-feed', [
                        'updates' => app(SystemUpdateService::class)->previewUpdatesForPanel('hr'),
                        'emptyMessage' => 'No updates or notes currently target the HR portal.',
                    ])),

                Action::make('previewEmployeePopup')
                    ->label('Preview Employee Popup')
                    ->icon(Heroicon::UserGroup)
                    ->color('info')
                    ->modalHeading(fn (): string => $this->modalHeading ?: 'System Updates & Notes (Employee Preview)')
                    ->modalDescription('Preview of Updates & Notes visible to Employees & Station Approvers.')
                    ->modalWidth('2xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close Preview')
                    ->modalContent(fn () => view('filament.components.system-updates-feed', [
                        'updates' => app(SystemUpdateService::class)->previewUpdatesForPanel('employee'),
                        'emptyMessage' => 'No updates or notes currently target the Employee portal.',
                    ])),
            ])
                ->label('Preview Popup')
                ->icon(Heroicon::Eye)
                ->color('gray')
                ->button(),

            Action::make('resetAllReads')
                ->label('Reset Read Status')
                ->icon(Heroicon::ArrowPath)
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Reset Read Confirmations for All Users?')
                ->modalDescription('This clears read confirmations and daily popup history so HR and Employees see active published updates on their next login.')
                ->visible(fn (): bool => auth()->user()?->can('Update:SystemUpdate') ?? false)
                ->action(function (): void {
                    app(SystemUpdateService::class)->resetAcknowledgments();
                    $this->dispatch('system-updates-acknowledged');

                    Notification::make()
                        ->success()
                        ->title('All user read confirmations have been reset')
                        ->send();
                }),

            CreateAction::make()
                ->label('New Update / Note')
                ->icon(Heroicon::Plus),
        ];
    }

    /**
     * @return array{published: int, drafts: int, pinned: int, acknowledgments: int, can_manage_settings: bool}
     */
    public function getSystemUpdateMetrics(): array
    {
        $published = SystemUpdate::query()->where('is_published', true)->count();
        $drafts = SystemUpdate::query()->where('is_published', false)->count();
        $pinned = SystemUpdate::query()->where('is_published', true)->where('is_pinned', true)->count();
        $acknowledgments = SystemUpdateAcknowledgment::query()->count();

        return [
            'published' => $published,
            'drafts' => $drafts,
            'pinned' => $pinned,
            'acknowledgments' => $acknowledgments,
            'can_manage_settings' => auth()->user()?->can('Update:SystemUpdate') ?? false,
        ];
    }
}
