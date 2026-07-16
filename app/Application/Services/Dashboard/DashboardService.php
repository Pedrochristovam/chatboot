<?php

namespace Application\Services\Dashboard;

use Domain\Shared\Enums\AgentStatus;
use Domain\Shared\Enums\ConversationStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Infrastructure\Persistence\Eloquent\Models\Client;
use Infrastructure\Persistence\Eloquent\Models\Conversation;
use Infrastructure\Persistence\Eloquent\Models\Message;
use Infrastructure\Persistence\Eloquent\Models\User;

class DashboardService
{
    public function getMetrics(): array
    {
        $today = Carbon::today();

        $conversationsToday = Conversation::query()
            ->whereDate('created_at', $today)
            ->count();

        $activeClients = Client::query()
            ->where('status', 'active')
            ->count();

        $onlineAgents = User::query()
            ->where('status', AgentStatus::Online)
            ->count();

        $waitingConversations = Conversation::query()
            ->where('status', ConversationStatus::Waiting)
            ->count();

        $closedToday = Conversation::query()
            ->where('status', ConversationStatus::Closed)
            ->whereDate('closed_at', $today)
            ->count();

        $avgResponseTime = $this->averageResponseTimeMinutes();

        return [
            'conversations_today' => $conversationsToday,
            'active_clients' => $activeClients,
            'online_agents' => $onlineAgents,
            'avg_response_time' => $avgResponseTime,
            'waiting_conversations' => $waitingConversations,
            'closed_today' => $closedToday,
        ];
    }

    public function getAttendanceChart(int $days = 7): array
    {
        $start = Carbon::today()->subDays($days - 1);

        $data = Conversation::query()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', $start)
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->all();

        $labels = [];
        $values = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i)->toDateString();
            $labels[] = Carbon::parse($date)->format('d/m');
            $values[] = $data[$date] ?? 0;
        }

        return compact('labels', 'values');
    }

    public function getMonthlyChart(): array
    {
        $start = Carbon::now()->startOfMonth()->subMonths(5);

        $conversations = Conversation::query()
            ->where('created_at', '>=', $start)
            ->get(['created_at']);

        $grouped = $conversations->groupBy(
            fn ($c) => $c->created_at->format('Y-m')
        )->map->count();

        $labels = [];
        $values = [];

        for ($i = 0; $i < 6; $i++) {
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');
            $labels[] = $month->format('M/Y');
            $values[] = $grouped[$key] ?? 0;
        }

        return compact('labels', 'values');
    }

    public function getRecentConversations(int $limit = 10)
    {
        return Conversation::query()
            ->with(['client', 'assignedAgent'])
            ->orderByDesc('last_message_at')
            ->limit($limit)
            ->get();
    }

    private function averageResponseTimeMinutes(): float
    {
        $conversations = Conversation::query()
            ->whereNotNull('first_response_at')
            ->whereDate('created_at', Carbon::today())
            ->get(['created_at', 'first_response_at']);

        if ($conversations->isEmpty()) {
            return 0;
        }

        $totalMinutes = $conversations->sum(
            fn ($c) => $c->created_at->diffInMinutes($c->first_response_at)
        );

        return round($totalMinutes / $conversations->count(), 1);
    }
}
