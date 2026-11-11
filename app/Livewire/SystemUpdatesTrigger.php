<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\SystemUpdateService;
use Filament\Facades\Filament;
use Livewire\Attributes\On;
use Livewire\Component;

class SystemUpdatesTrigger extends Component
{
    public string $panelId = '';

    public bool $shouldRender = false;

    public int $unreadCount = 0;

    public int $totalCount = 0;

    public function mount(?string $panelId = null): void
    {
        $this->panelId = $panelId ?: (string) (Filament::getCurrentPanel()?->getId() ?? '');
        $this->refreshCounts();
    }

    #[On('system-updates-acknowledged')]
    public function refreshCounts(): void
    {
        $service = app(SystemUpdateService::class);
        $this->shouldRender = false;
        $this->unreadCount = 0;
        $this->totalCount = 0;

        if (! $service->tablesExist() || ! in_array($this->panelId, ['hr', 'employee'], true)) {
            return;
        }

        if (! $service->settings()->show_topbar_button) {
            return;
        }

        $user = auth('web')->user();

        if (! $user instanceof User || ! $service->userMatchesPanel($user, $this->panelId)) {
            return;
        }

        $updates = $service->visibleUpdatesForPanel($user, $this->panelId);

        $this->shouldRender = true;
        $this->totalCount = $updates->count();
        $this->unreadCount = $updates
            ->reject(fn ($update): bool => (bool) $update->getAttribute('is_acknowledged'))
            ->count();
    }

    public function openUpdatesModal(): void
    {
        $this->dispatch('open-system-updates-modal');
    }

    public function render()
    {
        return view('livewire.system-updates-trigger');
    }
}
