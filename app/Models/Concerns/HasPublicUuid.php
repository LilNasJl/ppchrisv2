<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait HasPublicUuid
{
    protected static array $publicUuidColumnCache = [];

    protected static function bootHasPublicUuid(): void
    {
        static::creating(function (Model $model): void {
            if (! $model->hasPublicUuidColumn()) {
                return;
            }

            if (blank($model->getAttribute('uuid'))) {
                $model->setAttribute('uuid', (string) Str::uuid());
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function publicKey(): int|string|null
    {
        return $this->getAttribute('uuid') ?: $this->getKey();
    }

    public static function resolvePublicId(mixed $key): ?int
    {
        if (blank($key)) {
            return null;
        }

        /** @var Model&self $model */
        $model = new static;

        if ($model->hasPublicUuidColumn()) {
            $id = static::query()
                ->where('uuid', (string) $key)
                ->value($model->getKeyName());

            if (filled($id)) {
                return (int) $id;
            }
        }

        return is_numeric($key) ? (int) $key : null;
    }

    public static function findByPublicKey(mixed $key): ?Model
    {
        $id = static::resolvePublicId($key);

        return filled($id) ? static::query()->find($id) : null;
    }

    protected function hasPublicUuidColumn(): bool
    {
        $connection = $this->getConnectionName() ?: config('database.default');
        $cacheKey = $connection.'.'.$this->getTable();

        return static::$publicUuidColumnCache[$cacheKey] ??= Schema::connection($connection)
            ->hasColumn($this->getTable(), 'uuid');
    }
}
