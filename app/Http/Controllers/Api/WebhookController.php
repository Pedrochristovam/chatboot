<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIncomingWhatsAppJob;
use App\Jobs\ProcessWhatsAppStatusJob;
use Application\Contracts\WhatsApp\WhatsAppProviderInterface;
use Application\Services\WhatsApp\WhatsAppConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Infrastructure\WhatsApp\MetaCloudProvider;

class WebhookController extends Controller
{
    public function __construct(
        private readonly WhatsAppProviderInterface $provider,
        private readonly WhatsAppConfigService $whatsappConfig,
    ) {}

    public function verify(Request $request): mixed
    {
        $token = $this->whatsappConfig->webhookVerifyToken();

        if ($request->get('hub_mode') === 'subscribe'
            && $request->get('hub_verify_token') === $token) {
            return response($request->get('hub_challenge'), 200)
                ->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function receive(Request $request): JsonResponse
    {
        $payload = $request->all();

        if (empty($payload)) {
            return response()->json(['status' => 'empty']);
        }

        // Status de entrega/leitura da Meta (preparado para produção)
        $statuses = $payload['entry'][0]['changes'][0]['value']['statuses'] ?? null;
        if (is_array($statuses) && $statuses !== []) {
            foreach ($statuses as $statusRow) {
                $waId = $statusRow['id'] ?? null;
                $status = $statusRow['status'] ?? null;
                if (! $waId || ! $status) {
                    continue;
                }

                ProcessWhatsAppStatusJob::dispatch(
                    whatsappMessageId: $waId,
                    status: $status,
                    providerEventId: ($statusRow['id'] ?? '').':'.$status.':'.($statusRow['timestamp'] ?? ''),
                    payload: $statusRow,
                    occurredAt: isset($statusRow['timestamp'])
                        ? now()->setTimestamp((int) $statusRow['timestamp'])->toIso8601String()
                        : null,
                );
            }

            return response()->json(['status' => 'statuses_queued', 'count' => count($statuses)]);
        }

        $dto = $this->provider->receiveWebhook($payload);

        if (empty($dto->from)) {
            return response()->json(['status' => 'ignored']);
        }

        dispatch(ProcessIncomingWhatsAppJob::fromDto($dto));

        return response()->json(['status' => 'queued']);
    }
}
