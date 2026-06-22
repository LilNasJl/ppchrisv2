<?php

namespace App\Services;

use App\Models\Dtr;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DtrDayPartService
{
    public const WHOLE_DAY = 'whole_day';

    public const MORNING = 'morning';

    public const AFTERNOON = 'afternoon';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_IMPORTED = 'imported';

    public const SOURCE_LEAVE = 'leave';

    public const SOURCE_ABSENCE = 'absence';

    /**
     * @return array<string, string>
     */
    public function dayPartOptions(): array
    {
        return [
            self::MORNING => 'Morning',
            self::AFTERNOON => 'Afternoon',
        ];
    }

    public function normalize(?string $dayPart): string
    {
        $value = Str::of($dayPart ?? self::WHOLE_DAY)
            ->lower()
            ->replace([' ', '-'], '_')
            ->toString();

        return in_array($value, [self::MORNING, self::AFTERNOON], true)
            ? $value
            : self::WHOLE_DAY;
    }

    public function label(?string $dayPart): string
    {
        return match ($this->normalize($dayPart)) {
            self::MORNING => 'Morning',
            self::AFTERNOON => 'Afternoon',
            default => 'Whole Day',
        };
    }

    /**
     * @param  Collection<int, Dtr>  $records
     */
    public function openHalfDayPart(Collection $records): ?string
    {
        $parts = $records
            ->map(fn (Dtr $record): string => $this->normalize($record->day_part ?? null))
            ->unique()
            ->values();

        if ($parts->contains(self::WHOLE_DAY)) {
            return null;
        }

        if ($parts->contains(self::MORNING) && ! $parts->contains(self::AFTERNOON)) {
            return self::AFTERNOON;
        }

        if ($parts->contains(self::AFTERNOON) && ! $parts->contains(self::MORNING)) {
            return self::MORNING;
        }

        return null;
    }

    /**
     * @param  Collection<int, Dtr>  $records
     */
    public function conflictsWith(Collection $records, ?string $dayPart): bool
    {
        $newPart = $this->normalize($dayPart);

        return $records->contains(function (Dtr $record) use ($newPart): bool {
            $existingPart = $this->normalize($record->day_part ?? null);

            return $newPart === self::WHOLE_DAY
                || $existingPart === self::WHOLE_DAY
                || $existingPart === $newPart;
        });
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function scheduleWindow(
        string $date,
        ?string $scheduleStart,
        ?string $scheduleEnd,
        ?string $dayPart,
    ): array {
        $dayPart = $this->normalize($dayPart);

        if ($dayPart === self::WHOLE_DAY || blank($scheduleStart) || blank($scheduleEnd)) {
            return [$scheduleStart, $scheduleEnd];
        }

        $start = Carbon::parse("{$date} {$scheduleStart}");
        $end = Carbon::parse("{$date} {$scheduleEnd}");

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        $breakStart = Carbon::parse("{$date} 12:00:00");
        $breakEnd = Carbon::parse("{$date} 13:00:00");

        if (! $this->windowOverlaps($start, $end, $breakStart, $breakEnd)) {
            $midpoint = $start->copy()->addMinutes((int) floor($start->diffInMinutes($end) / 2));

            return $dayPart === self::MORNING
                ? [$start->format('H:i:s'), $midpoint->format('H:i:s')]
                : [$midpoint->format('H:i:s'), $end->format('H:i:s')];
        }

        return $dayPart === self::MORNING
            ? [$start->format('H:i:s'), $breakStart->format('H:i:s')]
            : [$breakEnd->format('H:i:s'), $end->format('H:i:s')];
    }

    public function payableMinutes(string $date, ?string $scheduleStart, ?string $scheduleEnd, bool $deductNoonBreak): int
    {
        if (blank($scheduleStart) || blank($scheduleEnd)) {
            return 0;
        }

        $start = Carbon::parse("{$date} {$scheduleStart}");
        $end = Carbon::parse("{$date} {$scheduleEnd}");

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        $minutes = (int) $start->diffInMinutes($end);

        if (! $deductNoonBreak) {
            return max(0, $minutes);
        }

        $breakStart = Carbon::parse("{$date} 12:00:00");
        $breakEnd = Carbon::parse("{$date} 13:00:00");

        return max(0, $minutes - $this->overlapMinutes($start, $end, $breakStart, $breakEnd));
    }

    public function absenceMinutes(string $date, ?string $dayPart): int
    {
        $dayPart = $this->normalize($dayPart);

        if (Carbon::parse($date)->isSaturday()) {
            return $dayPart === self::WHOLE_DAY ? 180 : 0;
        }

        return $dayPart === self::WHOLE_DAY ? 480 : 240;
    }

    protected function windowOverlaps(Carbon $start, Carbon $end, Carbon $windowStart, Carbon $windowEnd): bool
    {
        return $this->overlapMinutes($start, $end, $windowStart, $windowEnd) > 0;
    }

    protected function overlapMinutes(Carbon $start, Carbon $end, Carbon $windowStart, Carbon $windowEnd): int
    {
        $overlapStart = $start->greaterThan($windowStart) ? $start : $windowStart;
        $overlapEnd = $end->lessThan($windowEnd) ? $end : $windowEnd;

        return $overlapEnd->greaterThan($overlapStart)
            ? (int) $overlapStart->diffInMinutes($overlapEnd)
            : 0;
    }
}
