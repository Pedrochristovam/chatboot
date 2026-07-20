<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Infrastructure\Persistence\Eloquent\Models\WebhookReceipt;

class PruneOperationalDataCommand extends Command
{
    protected $signature = 'operations:prune {--days=30}';

    protected $description = 'Prune old processed webhook receipts and failed queue records';

    public function handle(): int
    {
        $before = now()->subDays(max(7, (int) $this->option('days')));
        $receipts = WebhookReceipt::query()
            ->where('processing_status', 'processed')
            ->where('processed_at', '<', $before)
            ->delete();
        $failedJobs = DB::table('failed_jobs')->where('failed_at', '<', $before)->delete();

        $this->info("Pruned {$receipts} receipt(s) and {$failedJobs} failed job(s).");

        return self::SUCCESS;
    }
}
