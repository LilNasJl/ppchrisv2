<?php

namespace App\Services;

use App\Models\SystemUpdate;
use App\Models\SystemUpdateAcknowledgment;
use App\Models\SystemUpdateSetting;
use App\Models\SystemUpdateUserState;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use STS\FilamentImpersonate\ImpersonateManager;
use Throwable;

class SystemUpdateService
{
    public const SESSION_LOGIN_PENDING = 'system_updates.login_pending';

    public const SESSION_DISMISSED = 'system_updates.dismissed_for_session';

    public function tablesExist(): bool
    {
        return Schema::hasTable('system_updates')
            && Schema::hasTable('system_update_settings')
            && Schema::hasTable('system_update_acknowledgments')
            && Schema::hasTable('system_update_user_states');
    }

    public function isImpersonating(): bool
    {
        try {
            return app(ImpersonateManager::class)->isImpersonating();
        } catch (Throwable) {
            return session()->has(ImpersonateManager::SESSION_KEY);
        }
    }

    public function userMatchesPanel(?Authenticatable $user, string $panelId): bool
    {
        if (! $user instanceof User || (bool) $user->is_disabled || $user->trashed()) {
            return false;
        }

        return match ($panelId) {
            'hr' => in_array($user->role, ['hr', 'admin'], true),
            'employee' => $user->role === 'employee' && ! (bool) $user->employee?->hasEndedEmployment(),
            default => false,
        };
    }

    public function markLoginPending(?Authenticatable $user = null): void
    {
        if ($this->isImpersonating()) {
            return;
        }

        if ($user !== null && ! ($user instanceof User && in_array($user->role, ['hr', 'admin', 'employee'], true))) {
            return;
        }

        session()->put(self::SESSION_LOGIN_PENDING, true);
        session()->forget(self::SESSION_DISMISSED);
    }

    public function settings(): SystemUpdateSetting
    {
        return SystemUpdateSetting::current();
    }

    /**
     * @return Collection<int, SystemUpdate>
     */
    public function visibleUpdatesForPanel(User $user, string $panelId): Collection
    {
        if (! $this->tablesExist() || ! $this->userMatchesPanel($user, $panelId)) {
            return collect();
        }

        $updates = SystemUpdate::query()
            ->published()
            ->forPanelAndUser($user, $panelId)
            ->ordered()
            ->get();

        if ($updates->isEmpty()) {
            return $updates;
        }

        $acknowledgedIds = SystemUpdateAcknowledgment::query()
            ->where('user_id', $user->id)
            ->whereIn('system_update_id', $updates->pluck('id'))
            ->pluck('system_update_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $updates->each(function (SystemUpdate $update) use ($acknowledgedIds): void {
            $update->setAttribute('is_acknowledged', in_array((int) $update->id, $acknowledgedIds, true));
        });

        return $updates;
    }

    /**
     * @return Collection<int, SystemUpdate>
     */
    public function unacknowledgedUpdatesForPanel(User $user, string $panelId): Collection
    {
        return $this->visibleUpdatesForPanel($user, $panelId)
            ->reject(fn (SystemUpdate $update): bool => (bool) $update->getAttribute('is_acknowledged'))
            ->values();
    }

    /**
     * @return Collection<int, SystemUpdate>
     */
    public function previewUpdatesForPanel(string $panelId): Collection
    {
        if (! $this->tablesExist()) {
            return collect();
        }

        $audiences = match ($panelId) {
            'hr' => [SystemUpdate::AUDIENCE_ALL, SystemUpdate::AUDIENCE_HR],
            'employee' => [SystemUpdate::AUDIENCE_ALL, SystemUpdate::AUDIENCE_EMPLOYEE, SystemUpdate::AUDIENCE_STATION_MANAGER],
            default => [],
        };

        if ($audiences === []) {
            return collect();
        }

        return SystemUpdate::query()
            ->published()
            ->whereIn('audience', $audiences)
            ->ordered()
            ->get()
            ->each(fn (SystemUpdate $update) => $update->setAttribute('is_acknowledged', false));
    }

    public function shouldAutoOpenModal(User $user, string $panelId): bool
    {
        if (! $this->tablesExist()) {
            return false;
        }

        if ($this->isImpersonating() || ! $this->userMatchesPanel($user, $panelId)) {
            return false;
        }

        if ((bool) session(self::SESSION_DISMISSED, false)) {
            return false;
        }

        if (! (bool) session(self::SESSION_LOGIN_PENDING, false)) {
            return false;
        }

        $setting = $this->settings();

        if ($setting->popup_frequency === SystemUpdateSetting::FREQUENCY_DISABLED) {
            return false;
        }

        $visible = $this->visibleUpdatesForPanel($user, $panelId);

        if ($visible->isEmpty()) {
            return false;
        }

        $hasUnacknowledged = $visible->contains(
            fn (SystemUpdate $update): bool => ! (bool) $update->getAttribute('is_acknowledged')
        );

        return match ($setting->popup_frequency) {
            SystemUpdateSetting::FREQUENCY_EVERY_LOGIN => true,
            SystemUpdateSetting::FREQUENCY_ONCE_PER_DAY => $hasUnacknowledged && ! $this->wasShownToday($user),
            default => $hasUnacknowledged,
        };
    }

    public function markAutoPopupOpened(User $user): void
    {
        session()->forget(self::SESSION_LOGIN_PENDING);

        if (! $this->tablesExist() || $this->isImpersonating()) {
            return;
        }

        SystemUpdateUserState::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['last_popup_shown_at' => now()],
        );
    }

    public function dismissForSession(): void
    {
        session()->put(self::SESSION_DISMISSED, true);
        session()->forget(self::SESSION_LOGIN_PENDING);
    }

    /**
     * @param  array<int, int|string>  $updateIds
     */
    public function acknowledgeUpdates(User $user, array $updateIds): void
    {
        $this->dismissForSession();

        if (! $this->tablesExist() || $this->isImpersonating()) {
            return;
        }

        $ids = collect($updateIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $now = now();
        $rows = $ids->map(fn (int $updateId): array => [
            'system_update_id' => $updateId,
            'user_id' => $user->id,
            'acknowledged_at' => $now,
        ])->all();

        SystemUpdateAcknowledgment::query()->insertOrIgnore($rows);
    }

    public function resetAcknowledgments(?int $updateId = null): void
    {
        if (! $this->tablesExist()) {
            return;
        }

        if ($updateId !== null) {
            SystemUpdateAcknowledgment::query()
                ->where('system_update_id', $updateId)
                ->delete();
        } else {
            SystemUpdateAcknowledgment::query()->delete();
        }

        SystemUpdateUserState::query()->update(['last_popup_shown_at' => null]);
    }

    protected function wasShownToday(User $user): bool
    {
        $state = SystemUpdateUserState::query()
            ->where('user_id', $user->id)
            ->first();

        if (! $state?->last_popup_shown_at) {
            return false;
        }

        $timezone = config('app.timezone', 'Asia/Manila');

        return Carbon::parse($state->last_popup_shown_at)
            ->timezone($timezone)
            ->isSameDay(now($timezone));
    }
}
