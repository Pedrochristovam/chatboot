<?php

namespace Application\Services\Conversation;

use Application\Services\Settings\BusinessHoursService;
use Illuminate\Support\Carbon;
use Infrastructure\Persistence\Eloquent\Models\Conversation;
use Infrastructure\Persistence\Eloquent\Models\Setting;

class SlaService
{
    private ?int $responseMinutes = null;

    public function __construct(private readonly BusinessHoursService $businessHours) {}

    public function responseMinutes(): int
    {
        return $this->responseMinutes ??= max(1, (int) Setting::getValue('sla', 'first_response_minutes', 15));
    }

    public function dueAt(?Carbon $from = null): Carbon
    {
        return $this->businessHours->addBusinessMinutes(
            ($from ?? now())->copy(),
            $this->responseMinutes(),
        );
    }

    public function startWaiting(Conversation $conversation, ?Carbon $from = null): void
    {
        $from ??= now();
        $conversation->update([
            'waiting_since' => $from,
            'sla_due_at' => $this->dueAt($from),
        ]);
    }

    public function clear(Conversation $conversation): void
    {
        $conversation->update([
            'waiting_since' => null,
            'sla_due_at' => null,
        ]);
    }

    public function state(Conversation $conversation, ?Carbon $at = null): string
    {
        if (! $conversation->sla_due_at) {
            return 'none';
        }

        $at ??= now();
        if ($at->greaterThanOrEqualTo($conversation->sla_due_at)) {
            return 'overdue';
        }

        $warningAt = $conversation->sla_due_at->copy()
            ->subMinutes(max(1, (int) ceil($this->responseMinutes() * 0.25)));

        return $at->greaterThanOrEqualTo($warningAt) ? 'warning' : 'normal';
    }
}
