<?php

namespace App\Console\Commands;

use Application\Services\Messaging\FailedMessageService;
use Domain\Shared\Enums\MessageStatus;
use Illuminate\Console\Command;
use Infrastructure\Persistence\Eloquent\Models\Message;

class RetryFailedMessagesCommand extends Command
{
    protected $signature = 'messages:retry-failed {--limit=50} {--dry-run}';

    protected $description = 'Safely requeue failed outbound WhatsApp messages';

    public function handle(FailedMessageService $failedMessages): int
    {
        $messages = Message::query()
            ->where('status', MessageStatus::Failed)
            ->whereNull('whatsapp_message_id')
            ->oldest()
            ->limit(max(1, min(500, (int) $this->option('limit'))))
            ->get();

        if ($this->option('dry-run')) {
            $this->info($messages->count().' message(s) eligible for retry.');

            return self::SUCCESS;
        }

        $retried = 0;
        foreach ($messages as $message) {
            try {
                $failedMessages->retry($message);
                $retried++;
            } catch (\RuntimeException $e) {
                $this->warn("Message {$message->id}: {$e->getMessage()}");
            }
        }

        $this->info("{$retried} message(s) requeued.");

        return self::SUCCESS;
    }
}
