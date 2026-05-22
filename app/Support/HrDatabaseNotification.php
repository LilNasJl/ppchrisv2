<?php

namespace App\Support;

use App\Models\ActionHistory;
use App\Models\Dtr;
use App\Models\SystemAccount;
use App\Models\User;
use BackedEnum;
use DateTimeInterface;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
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

    public static function send(string $title, ?string $body = null, string $status = 'info', BackedEnum|string|null $icon = null): void
    {
        $actor = Auth::user();

        if (! $actor) {
            return;
        }

        $actorName = self::actorName($actor);
        $body = filled($body) ? "{$body} by {$actorName}" : "By {$actorName}";

        self::logCustomAction($title, $body, $status, $actor);

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

        self::logRecordAction($record, $action, $actor);

        self::sendNotification(
            title: self::titleFor($record, $action),
            body: self::bodyFor($record, $actor),
            status: self::statusFor($action),
            icon: self::iconFor($action),
            iconColor: self::colorFor($action),
        );
    }

    protected static function sendNotification(string $title, ?string $body, string $status, BackedEnum|string|null $icon, string $iconColor): void
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

    protected static function logRecordAction(Model $record, string $action, User $actor): void
    {
        if ($record instanceof ActionHistory) {
            return;
        }

        $changes = self::filteredChanges($record);
        $afterData = self::filteredHistoryData($record, $record->getAttributes());
        $beforeData = match ($action) {
            'created' => null,
            'updated' => self::beforeSnapshot($record, $afterData, $changes),
            default => self::filteredHistoryData($record, $record->getOriginal() ?: $record->getAttributes()),
        };

        if (in_array($action, ['deleted', 'permanently deleted'], true)) {
            $afterData = null;
        }

        ActionHistory::create([
            'actor_id' => $actor->id,
            'actor_name' => self::actorName($actor),
            'actor_role' => $actor->role,
            'action' => $action,
            'model_type' => $record::class,
            'model_id' => $record->getKey(),
            'model_label' => self::modelLabel($record),
            'record_label' => self::recordName($record),
            'summary' => self::bodyFor($record, $actor),
            'before_data' => $beforeData,
            'after_data' => $afterData,
            'changed_data' => self::changedHistoryData($record, $changes),
        ]);
    }

    protected static function logCustomAction(string $title, ?string $body, string $status, User $actor): void
    {
        ActionHistory::create([
            'actor_id' => $actor->id,
            'actor_name' => self::actorName($actor),
            'actor_role' => $actor->role,
            'action' => $status,
            'model_label' => 'system action',
            'record_label' => $title,
            'summary' => $body,
        ]);
    }

    protected static function filteredChanges(Model $record): array
    {
        return Arr::except($record->getChanges(), self::ignoredHistoryColumns());
    }

    protected static function beforeSnapshot(Model $record, array $afterData, array $changes): array
    {
        $previous = method_exists($record, 'getPrevious')
            ? $record->getPrevious()
            : [];

        $beforeData = $afterData;

        foreach (array_keys($changes) as $column) {
            if (array_key_exists($column, $previous)) {
                $beforeData[$column] = self::normalizeHistoryValue($previous[$column]);

                continue;
            }

            $beforeData[$column] = self::normalizeHistoryValue($record->getOriginal($column));
        }

        return $beforeData;
    }

    protected static function changedHistoryData(Model $record, array $changes): ?array
    {
        if ($changes === []) {
            return null;
        }

        $previous = method_exists($record, 'getPrevious')
            ? $record->getPrevious()
            : [];

        return collect($changes)
            ->mapWithKeys(function (mixed $value, string $column) use ($previous, $record): array {
                return [
                    $column => [
                        'before' => self::normalizeHistoryValue($previous[$column] ?? $record->getOriginal($column)),
                        'after' => self::normalizeHistoryValue($value),
                    ],
                ];
            })
            ->all();
    }

    protected static function filteredHistoryData(Model $record, array $data): array
    {
        $hidden = $record->getHidden();
        $ignored = array_unique([...self::ignoredHistoryColumns(), ...$hidden]);

        return collect($data)
            ->reject(fn (mixed $value, string $column): bool => in_array($column, $ignored, true))
            ->map(fn (mixed $value): mixed => self::normalizeHistoryValue($value))
            ->all();
    }

    protected static function normalizeHistoryValue(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_array($value)) {
            return collect($value)
                ->map(fn (mixed $item): mixed => self::normalizeHistoryValue($item))
                ->all();
        }

        if (is_object($value)) {
            return method_exists($value, '__toString') ? (string) $value : json_encode($value);
        }

        return $value;
    }

    protected static function ignoredHistoryColumns(): array
    {
        return [
            'password',
            'remember_token',
            'email_verified_at',
            'updated_at',
        ];
    }

    protected static function hasMeaningfulChanges(Model $record): bool
    {
        return collect(array_keys($record->getChanges()))
            ->reject(fn (string $column): bool => in_array($column, self::ignoredHistoryColumns(), true))
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
