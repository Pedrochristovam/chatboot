<?php

namespace App\Console\Commands;

use Application\Services\Messaging\ScheduledMessageService;
use Illuminate\Console\Command;

class DispatchScheduledMessagesCommand extends Command
{
    protected $signature = 'messages:dispatch-scheduled {--limit=50 : Máximo de mensagens por execução}';

    protected $description = 'Envia mensagens programadas que já venceram o horário';

    public function handle(ScheduledMessageService $service): int
    {
        $sent = $service->dispatchDue((int) $this->option('limit'));
        $this->info("Mensagens programadas disparadas: {$sent}");

        return self::SUCCESS;
    }
}
