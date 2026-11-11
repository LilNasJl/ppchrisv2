<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class SystemUpdateSetting extends Model
{
    public const FREQUENCY_UNTIL_ACKNOWLEDGED = 'until_acknowledged';

    public const FREQUENCY_ONCE_PER_DAY = 'once_per_day';

    public const FREQUENCY_EVERY_LOGIN = 'every_login';

    public const FREQUENCY_DISABLED = 'disabled';

    protected $fillable = [
        'popup_frequency',
        'show_topbar_button',
        'modal_heading',
        'modal_subtitle',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'show_topbar_button' => 'boolean',
        ];
    }

    public static function current(): self
    {
        $defaults = [
            'popup_frequency' => self::FREQUENCY_UNTIL_ACKNOWLEDGED,
            'show_topbar_button' => true,
            'modal_heading' => 'System Updates & Notes',
            'modal_subtitle' => 'Recent enhancements, attendance & payroll updates, and important operational reminders.',
        ];

        if (! Schema::hasTable('system_update_settings')) {
            return new self($defaults);
        }

        $setting = static::query()->first();

        if ($setting instanceof self) {
            return $setting;
        }

        return static::query()->create($defaults);
    }

    /**
     * @return array<string, string>
     */
    public static function frequencyOptions(): array
    {
        return [
            self::FREQUENCY_UNTIL_ACKNOWLEDGED => 'Until marked as read (Recommended)',
            self::FREQUENCY_ONCE_PER_DAY => 'Once per day on login',
            self::FREQUENCY_EVERY_LOGIN => 'Every login',
            self::FREQUENCY_DISABLED => 'Disabled (Topbar button only)',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function frequencyDescriptions(): array
    {
        return [
            self::FREQUENCY_UNTIL_ACKNOWLEDGED => 'Appears on login until the user clicks "Got it / Mark as Read". New or re-announced updates re-open it.',
            self::FREQUENCY_ONCE_PER_DAY => 'Appears at most once per calendar day upon login while unread or active updates exist.',
            self::FREQUENCY_EVERY_LOGIN => 'Appears automatically on every login whenever published updates exist for the user.',
            self::FREQUENCY_DISABLED => 'Never pops up automatically after login; users can still open updates from the topbar button if enabled.',
        ];
    }

    public static function frequencyLabel(?string $frequency): string
    {
        return self::frequencyOptions()[$frequency ?? self::FREQUENCY_UNTIL_ACKNOWLEDGED]
            ?? 'Until marked as read (Recommended)';
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
