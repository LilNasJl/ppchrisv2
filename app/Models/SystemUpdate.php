<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SystemUpdate extends Model
{
    use HasFactory, HasPublicUuid, SoftDeletes;

    public const AUDIENCE_ALL = 'all';

    public const AUDIENCE_HR = 'hr';

    public const AUDIENCE_EMPLOYEE = 'employee';

    public const AUDIENCE_STATION_MANAGER = 'station_manager';

    public const CATEGORY_UPDATE = 'update';

    public const CATEGORY_FEATURE = 'feature';

    public const CATEGORY_POLICY = 'policy';

    public const CATEGORY_FIX = 'fix';

    public const CATEGORY_NOTE = 'note';

    protected $fillable = [
        'version',
        'title',
        'summary',
        'category',
        'audience',
        'items',
        'notes',
        'is_pinned',
        'is_published',
        'published_at',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'is_pinned' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SystemUpdate $update): void {
            if ($update->is_published === null) {
                $update->is_published = true;
            }

            if ($update->is_published && $update->published_at === null) {
                $update->published_at = now();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public static function audienceOptions(): array
    {
        return [
            self::AUDIENCE_ALL => 'All Users (HR & Employee)',
            self::AUDIENCE_HR => 'HR & Admin Only',
            self::AUDIENCE_EMPLOYEE => 'All Employees',
            self::AUDIENCE_STATION_MANAGER => 'Station Approvers (SIC / RC)',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function categoryOptions(): array
    {
        return [
            self::CATEGORY_UPDATE => 'Update',
            self::CATEGORY_FEATURE => 'New Feature',
            self::CATEGORY_POLICY => 'Policy / Rule',
            self::CATEGORY_FIX => 'Fix & Performance',
            self::CATEGORY_NOTE => 'Important Note',
        ];
    }

    public static function audienceLabel(?string $audience): string
    {
        return self::audienceOptions()[$audience ?? self::AUDIENCE_ALL] ?? 'All Users (HR & Employee)';
    }

    public static function categoryLabel(?string $category): string
    {
        return self::categoryOptions()[$category ?? self::CATEGORY_UPDATE] ?? 'Update';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where(function (Builder $builder): void {
                $builder->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function scopeForPanelAndUser(Builder $query, User $user, string $panelId): Builder
    {
        $audiences = match ($panelId) {
            'hr' => [self::AUDIENCE_ALL, self::AUDIENCE_HR],
            'employee' => array_values(array_filter([
                self::AUDIENCE_ALL,
                self::AUDIENCE_EMPLOYEE,
                $user->isStationManager() ? self::AUDIENCE_STATION_MANAGER : null,
            ])),
            default => [],
        };

        if ($audiences === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('audience', $audiences);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByDesc('is_pinned')
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    public function acknowledgments(): HasMany
    {
        return $this->hasMany(SystemUpdateAcknowledgment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
