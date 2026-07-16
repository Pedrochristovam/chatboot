<?php

namespace Application\Services\Report;

use Domain\Shared\Enums\MessageSenderType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Infrastructure\Persistence\Eloquent\Models\Client;
use Infrastructure\Persistence\Eloquent\Models\Conversation;
use Infrastructure\Persistence\Eloquent\Models\Message;
use Infrastructure\Persistence\Eloquent\Models\User;

class ReportService
{
    public function getMetrics(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= Carbon::now()->startOfMonth();
        $to ??= Carbon::now();

        $conversations = Conversation::query()
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $messagesSent = Message::query()
            ->where('sender_type', MessageSenderType::Agent)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $avgResponse = $this->averageResponseTime($from, $to);

        $newClients = Client::query()
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $recurringClients = Conversation::query()
            ->whereBetween('created_at', [$from, $to])
            ->select('client_id')
            ->groupBy('client_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        return [
            'total_conversations' => $conversations,
            'messages_sent' => $messagesSent,
            'avg_response_time' => $avgResponse,
            'new_clients' => $newClients,
            'recurring_clients' => $recurringClients,
            'conversion_rate' => $conversations > 0
                ? round((Conversation::whereBetween('created_at', [$from, $to])->whereNotNull('resolved_at')->count() / $conversations) * 100, 1)
                : 0,
        ];
    }

    public function dailyChart(int $days = 7): array
    {
        $start = Carbon::today()->subDays($days - 1);
        $labels = [];
        $values = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $labels[] = $date->translatedFormat('D d/m');
            $values[] = Conversation::query()->whereDate('created_at', $date)->count();
        }

        return compact('labels', 'values');
    }

    public function messagesByAgent(): array
    {
        return Message::query()
            ->where('sender_type', MessageSenderType::Agent)
            ->select('sender_id', DB::raw('COUNT(*) as total'))
            ->groupBy('sender_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                $user = User::find($row->sender_id);

                return [
                    'name' => $user?->name ?? 'Desconhecido',
                    'total' => $row->total,
                ];
            })
            ->all();
    }

    public function departmentPerformance(): array
    {
        return Conversation::query()
            ->join('departments', 'conversations.department_id', '=', 'departments.id')
            ->select('departments.name', DB::raw('COUNT(*) as total'))
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'total' => $row->total,
                'avg_time' => '—',
                'status' => 'Normal',
            ])
            ->all();
    }

    private function averageResponseTime(Carbon $from, Carbon $to): string
    {
        $conversations = Conversation::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('first_response_at')
            ->get(['created_at', 'first_response_at']);

        if ($conversations->isEmpty()) {
            return '0m 0s';
        }

        $totalSeconds = $conversations->sum(
            fn ($c) => $c->created_at->diffInSeconds($c->first_response_at)
        );
        $avg = (int) ($totalSeconds / $conversations->count());
        $minutes = intdiv($avg, 60);
        $seconds = $avg % 60;

        return "{$minutes}m {$seconds}s";
    }
}
