<?php

namespace App\Livewire;

use App\Models\SystemUpdate;
use App\Models\User;
use App\Services\SystemUpdateService;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class SystemUpdatesModal extends Component
{
    public const MODAL_ID = 'global-system-updates-modal';

    public string $panelId = '';

    public bool $autoOpen = false;

    public bool $shouldRender = false;

    public function mount(?string $panelId = null): void
    {
        $service = app(SystemUpdateService::class);
        $this->panelId = $panelId ?: (string) (Filament::getCurrentPanel()?->getId() ?? '');

        if (! $service->tablesExist() || ! in_array($this->panelId, ['hr', 'employee'], true)) {
            return;
        }

        $user = auth('web')->user();

        if (! $user instanceof User || ! $service->userMatchesPanel($user, $this->panelId)) {
            return;
        }

        $this->shouldRender = true;
        $this->autoOpen = $service->shouldAutoOpenModal($user, $this->panelId);

        if ($this->autoOpen) {
            $service->markAutoPopupOpened($user);
        }
    }

    #[On('open-system-updates-modal')]
    public function openFromTrigger(): void
    {
        if (! $this->shouldRender) {
            return;
        }

        $this->dispatch('open-modal', id: self::MODAL_ID);
    }

    public function acknowledgeAll(): void
    {
        $service = app(SystemUpdateService::class);
        $user = auth('web')->user();

        if ($user instanceof User && $this->shouldRender) {
            $ids = $service->visibleUpdatesForPanel($user, $this->panelId)
                ->pluck('id')
                ->all();

            $service->acknowledgeUpdates($user, $ids);
        } else {
            $service->dismissForSession();
        }

        $this->autoOpen = false;

        $this->dispatch('system-updates-acknowledged');
        $this->dispatch('close-modal', id: self::MODAL_ID);
    }

    public function dismissModal(): void
    {
        app(SystemUpdateService::class)->dismissForSession();
        $this->autoOpen = false;
        $this->dispatch('close-modal', id: self::MODAL_ID);
    }

    public function dismissForSession(): void
    {
        app(SystemUpdateService::class)->dismissForSession();
        $this->autoOpen = false;
    }

    /**
     * @return Collection<int, SystemUpdate>
     */
    public function getVisibleUpdatesProperty(): Collection
    {
        if (! $this->shouldRender) {
            return collect();
        }

        $user = auth('web')->user();

        if (! $user instanceof User) {
            return collect();
        }

        return app(SystemUpdateService::class)->visibleUpdatesForPanel($user, $this->panelId);
    }

    public function getUnreadCountProperty(): int
    {
        return $this->visibleUpdates
            ->reject(fn (SystemUpdate $update): bool => (bool) $update->getAttribute('is_acknowledged'))
            ->count();
    }

    public function render()
    {
        $service = app(SystemUpdateService::class);
        $setting = $service->tablesExist() ? $service->settings() : null;

        return view('livewire.system-updates-modal', [
            'modalId' => self::MODAL_ID,
            'setting' => $setting,
            'visibleUpdates' => $this->visibleUpdates,
            'unreadCount' => $this->unreadCount,
            'isImpersonating' => $service->isImpersonating(),
        ]);
    }
}
