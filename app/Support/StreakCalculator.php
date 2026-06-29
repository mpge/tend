<?php

namespace App\Support;

use App\Enums\HabitCadence;
use Carbon\CarbonImmutable;

/**
 * Computes habit streaks from a list of completion dates.
 *
 * A "period" is a day for daily habits and an ISO week (Mon-Sun) for weekly
 * habits. A period counts as completed if it contains at least one entry.
 */
class StreakCalculator
{
    /**
     * The number of consecutive completed periods ending at the current period.
     *
     * If the current period is not yet completed the streak is still considered
     * alive from the previous period (so "not done today, yet" doesn't read as a
     * broken streak until the period actually lapses).
     *
     * @param  array<int, string>  $dates  completion dates as 'Y-m-d'
     */
    public function current(array $dates, CarbonImmutable $today, HabitCadence $cadence): int
    {
        $periods = $this->periodKeys($dates, $cadence);

        if ($periods === []) {
            return 0;
        }

        $cursor = $this->periodStart($today, $cadence);

        if (! in_array($this->key($cursor), $periods, true)) {
            $cursor = $this->step($cursor, $cadence, -1);

            if (! in_array($this->key($cursor), $periods, true)) {
                return 0;
            }
        }

        $streak = 0;

        while (in_array($this->key($cursor), $periods, true)) {
            $streak++;
            $cursor = $this->step($cursor, $cadence, -1);
        }

        return $streak;
    }

    /**
     * The longest run of consecutive completed periods on record.
     *
     * @param  array<int, string>  $dates  completion dates as 'Y-m-d'
     */
    public function longest(array $dates, HabitCadence $cadence): int
    {
        $periods = $this->periodKeys($dates, $cadence);

        if ($periods === []) {
            return 0;
        }

        $starts = array_map(
            fn (string $key): CarbonImmutable => CarbonImmutable::parse($key),
            $periods,
        );
        usort($starts, fn (CarbonImmutable $a, CarbonImmutable $b): int => $a <=> $b);

        $longest = 1;
        $run = 1;

        for ($i = 1, $count = count($starts); $i < $count; $i++) {
            $expected = $this->step($starts[$i - 1], $cadence, 1);

            $run = $this->key($starts[$i]) === $this->key($expected) ? $run + 1 : 1;
            $longest = max($longest, $run);
        }

        return $longest;
    }

    /**
     * Unique period keys (each a 'Y-m-d' period-start date) for the given dates.
     *
     * @param  array<int, string>  $dates
     * @return array<int, string>
     */
    private function periodKeys(array $dates, HabitCadence $cadence): array
    {
        $keys = array_map(
            fn (string $date): string => $this->key($this->periodStart(CarbonImmutable::parse($date), $cadence)),
            $dates,
        );

        return array_values(array_unique($keys));
    }

    private function periodStart(CarbonImmutable $date, HabitCadence $cadence): CarbonImmutable
    {
        return match ($cadence) {
            HabitCadence::Daily => $date->startOfDay(),
            HabitCadence::Weekly => $date->startOfWeek(),
        };
    }

    private function step(CarbonImmutable $periodStart, HabitCadence $cadence, int $amount): CarbonImmutable
    {
        return match ($cadence) {
            HabitCadence::Daily => $periodStart->addDays($amount),
            HabitCadence::Weekly => $periodStart->addWeeks($amount),
        };
    }

    private function key(CarbonImmutable $periodStart): string
    {
        return $periodStart->format('Y-m-d');
    }
}
