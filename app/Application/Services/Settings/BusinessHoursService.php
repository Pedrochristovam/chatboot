<?php

namespace Application\Services\Settings;

use Illuminate\Support\Carbon;
use Infrastructure\Persistence\Eloquent\Models\Setting;

class BusinessHoursService
{
    public function isOpen(?Carbon $at = null): bool
    {
        $at ??= now();
        $days = $this->openDays();
        $weekday = (int) $at->dayOfWeekIso; // 1=Mon … 7=Sun

        if (! in_array($weekday, $days, true)) {
            return false;
        }

        [$start, $end] = $this->boundsFor($at);

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
            if ($at->lessThan($start)) {
                $at = $at->copy()->addDay();
            }
        }

        return $at->betweenIncluded($start, $end);
    }

    public function nextOpen(?Carbon $at = null): Carbon
    {
        $cursor = ($at ?? now())->copy()->startOfMinute();

        for ($i = 0; $i < 14; $i++) {
            if (in_array((int) $cursor->dayOfWeekIso, $this->openDays(), true)) {
                [$start, $end] = $this->boundsFor($cursor);
                if ($cursor->lessThan($start)) {
                    return $start;
                }
                if ($cursor->betweenIncluded($start, $end)) {
                    return $cursor;
                }
            }
            $cursor->addDay()->startOfDay();
        }

        return $cursor;
    }

    public function addBusinessMinutes(Carbon $from, int $minutes): Carbon
    {
        $remaining = max(0, $minutes);
        $cursor = $this->nextOpen($from);

        while ($remaining > 0) {
            [$start, $end] = $this->boundsFor($cursor);
            if ($end->lessThanOrEqualTo($start)) {
                $end->addDay();
            }

            $available = max(0, $cursor->diffInMinutes($end, false));
            if ($remaining <= $available) {
                return $cursor->addMinutes($remaining);
            }

            $remaining -= $available;
            $cursor = $this->nextOpen($end->copy()->addMinute());
        }

        return $cursor;
    }

    public function startTime(): string
    {
        return (string) Setting::getValue('business_hours', 'start', '08:00');
    }

    public function endTime(): string
    {
        return (string) Setting::getValue('business_hours', 'end', '18:00');
    }

    /** @return list<int> */
    public function openDays(): array
    {
        $days = Setting::getValue('business_hours', 'days', [1, 2, 3, 4, 5]);

        if (is_string($days)) {
            $days = json_decode($days, true) ?: [1, 2, 3, 4, 5];
        }

        return array_map('intval', (array) $days);
    }

    public function afterHoursMessage(): string
    {
        return (string) Setting::getValue(
            'notifications',
            'after_hours_message',
            'Olá! Nosso horário de atendimento é de segunda a sexta, das 08:00 às 18:00. Retornaremos assim que possível.'
        );
    }

    /** @return array{Carbon, Carbon} */
    private function boundsFor(Carbon $day): array
    {
        $start = $day->copy()->setTimeFromTimeString($this->startTime());
        $end = $day->copy()->setTimeFromTimeString($this->endTime());

        return [$start, $end];
    }
}
