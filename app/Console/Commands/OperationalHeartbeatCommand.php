<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class OperationalHeartbeatCommand extends Command
{
    protected $signature = 'operations:heartbeat';

    protected $description = 'Record scheduler activity for operational health checks';

    public function handle(): int
    {
        Cache::put('operations.scheduler.last_run', now(), now()->addMinutes(5));

        return self::SUCCESS;
    }
}
