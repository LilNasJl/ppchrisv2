<?php

namespace App\Services;

use App\Models\AccountLog;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AccountLogService
{
    public function record(string $event, ?User $user = null, ?User $actor = null, ?string $panel = null): ?AccountLog
    {
        if (! Schema::hasTable('account_logs')) {
            return null;
        }

        return AccountLog::create([
            'user_id' => $user?->id,
            'actor_user_id' => $actor?->id,
            'event' => $event,
            'panel' => $panel ?: $this->currentPanel(),
            'username' => $user?->username,
            'account_name' => $this->accountName($user),
            'role' => $user?->role,
            'ip_address' => request()?->ip(),
            'session_id' => session()->getId(),
            'user_agent' => request()?->userAgent(),
            'occurred_at' => now(),
        ]);
    }

    protected function accountName(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        return $user->employee?->full_name
            ?: $user->name
            ?: $user->username;
    }

    protected function currentPanel(): ?string
    {
        try {
            return Filament::getCurrentPanel()?->getId();
        } catch (Throwable) {
            return $this->panelFromPath();
        }
    }

    protected function panelFromPath(): ?string
    {
        $path = trim(request()?->path() ?? '', '/');

        return match (true) {
            str_starts_with($path, 'employee') => 'employee',
            str_starts_with($path, 'hr') => 'hr',
            default => null,
        };
    }
}
