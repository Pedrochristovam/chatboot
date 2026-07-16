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

        $start = $this->startTime();
        $end = $this->endTime();
        $current = $at->format('H:i');

        return $current >= $start && $current <= $end;
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
}
