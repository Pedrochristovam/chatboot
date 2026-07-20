<?php

namespace App\Console\Commands;

use Application\Services\Conversation\AgentPresenceService;
use Illuminate\Console\Command;

class RequeueOfflineAgentsCommand extends Command
{
    protected $signature = 'agents:requeue-offline {--after=150 : Seconds without heartbeat}';

    protected $description = 'Return conversations from disconnected agents to the queue';

    public function handle(AgentPresenceService $presence): int
    {
        $count = $presence->requeueStaleAgents(max(60, (int) $this->option('after')));
        $this->info("{$count} conversation(s) returned to queue.");

        return self::SUCCESS;
    }
}
