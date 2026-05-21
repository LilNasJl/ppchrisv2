<?php

namespace App\Support;

use App\Models\Dtr;
use App\Models\SystemAccount;
use App\Models\User;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use Illuminate\Support\Str;

class HrDatabaseNotification
{
    public static function recordCreated(Model $record): void
    {
        self::sendForRecordAction($record, 'created');
    }

    public static function recordUpdated(Model $record): void
    {
        if (! self::hasMeaningfulChanges($record)) {
            return;
        }

        self::sendForRecordAction($record, 'updated');
    }

    public static function recordDeleted(Model $record): void
    {
        self::sendForRecordAction($record, 'deleted');
    }

    public static function recordRestored(Model $record): void
    {
        self::sendForRecordAction($record, 'restored');
    }

    public static function recordForceDeleted(Model $record): void
    {
        self::sendForRecordAction($record, 'permanently deleted');
    }

    public static function send(string $title, ?string $body = null, string $status = 'info', BackedEnum | string | null $icon = null): void
    {
        $actor = Auth::user();

        if (! $actor) {
            return;
        }

        $actorName = self::actorName($actor);
        $body = filled($body) ? "{$body} by {$actorName}" : "By {$actorName}";

        self::sendNotification(
            title: $title,
            body: $body,
            status: $status,
            icon: $icon ?? self::iconFor($status),
            iconColor: self::colorFor($status),
        );
    }

    protected static function sendForRecordAction(Model $record, string $action): void
    {
        $actor = Auth::user();

        if (! $actor) {
            return;
        }

        self::sendNotification(
            title: self::titleFor($record, $action),
            body: self::bodyFor($record, $actor),
            status: self::statusFor($action),
            icon: self::iconFor($action),
            iconColor: self::colorFor($action),
        );
    }

    protected static function sendNotification(string $title, ?string $body, string $status, BackedEnum | string | null $icon, string $iconColor): void
    {
        $recipients = User::query()
            ->whereIn('role', ['admin', 'hr'])
            ->where('is_disabled', false)
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $notification = FilamentNotification::make()
            ->title($title)
            ->body($body)
            ->icon($icon)
            ->iconColor($iconColor)
            ->status($status);

        LaravelNotification::sendNow($recipients, $notification->toDatabase());
    }

    protected static function hasMeaningfulChanges(Model $record): bool
    {
        $ignoredColumns = [
            'updated_at',
            'remember_token',
            'email_verified_at',
        ];

        return collect(array_keys($record->getChanges()))
            ->reject(fn (string $column): bool => in_array($column, $ignoredColumns, true))
            ->isNotEmpty();
    }

    protected static function titleFor(Model $record, string $action): string
    {
        return Str::headline(self::modelLabel($record)).' '.$action;
    }

    protected static function bodyFor(Model $record, User $actor): string
    {
        $recordName = self::recordName($record);
        $actorName = self::actorName($actor);

        return $recordName
            ? "{$recordName} by {$actorName}"
            : "Record #{$record->getKey()} by {$actorName}";
    }

    protected static function actorName(User $actor): string
    {
        return $actor->username ?: $actor->name ?: $actor->email ?: 'System user';
    }

    protected static function modelLabel(Model $record): string
    {
        if ($record instanceof SystemAccount) {
            return 'system account';
        }

        if ($record instanceof User) {
            return $record->role === 'employee' ? 'employee account' : 'system account';
        }

        return Str::of(class_basename($record))
            ->headline()
            ->lower()
            ->toString();
    }

    protected static function recordName(Model $record): ?string
    {
        if ($record instanceof Dtr) {
            return trim("DTR {$record->date_in} {$record->time_in} - {$record->fingerprint_id}");
        }

        foreach ([
            'full_name',
            'title',
            'name',
            'username',
            'branch_name',
            'type',
            'leave_type',
            'batch_id',
            'uid',
            'email',
        ] as $attribute) {
            $value = data_get($record, $attribute);

            if (filled($value)) {
                return (string) $value;
            }
        }

        return $record->getKey() ? '#'.$record->getKey() : null;
    }

    protected static function iconFor(string $action): Heroicon
    {
        return match ($action) {
            'created' => Heroicon::PlusCircle,
            'updated' => Heroicon::PencilSquare,
            'restored' => Heroicon::ArrowPath,
            'deleted',
            'permanently deleted' => Heroicon::Trash,
            default => Heroicon::Bell,
        };
    }

    protected static function colorFor(string $action): string
    {
        return match ($action) {
            'created' => 'success',
            'updated' => 'warning',
            'restored' => 'info',
            'deleted',
            'permanently deleted' => 'danger',
            default => 'primary',
        };
    }

    protected static function statusFor(string $action): string
    {
        return match ($action) {
            'created' => 'success',
            'updated' => 'warning',
            'restored' => 'info',
            'deleted',
            'permanently deleted' => 'danger',
            default => 'info',
        };
    }
}
