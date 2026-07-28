<?php

namespace App\Services;

use App\Models\AccountLog;
use App\Models\KpiAccount;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AccountLogService
{
    public function record(
        string $event,
        ?Authenticatable $user = null,
        ?Authenticatable $actor = null,
        ?string $panel = null,
    ): ?AccountLog {
        if (! Schema::hasTable('account_logs')) {
            return null;
        }

        return AccountLog::create([
            'user_id' => $user instanceof User ? $user->id : null,
            'actor_user_id' => $actor instanceof User ? $actor->id : null,
            'event' => $event,
            'panel' => $panel ?: $this->currentPanel(),
            'username' => $user?->username,
            'account_name' => $this->accountName($user),
            'role' => match (true) {
                $user instanceof User => $user->role,
                $user instanceof KpiAccount => 'kpi',
                default => null,
            },
            'ip_address' => request()?->ip(),
            'session_id' => session()->getId(),
            'user_agent' => request()?->userAgent(),
            'occurred_at' => now(),
        ]);
    }

    protected function accountName(?Authenticatable $user): ?string
    {
        if (! $user) {
            return null;
        }

        if ($user instanceof User) {
            return $user->employee?->full_name
                ?: $user->name
                ?: $user->username;
        }

        return $user instanceof KpiAccount ? $user->username : null;
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
            str_starts_with($path, 'kpi') => 'kpi',
            str_starts_with($path, 'employee') => 'employee',
            str_starts_with($path, 'hr') => 'hr',
            default => null,
        };
    }
}
