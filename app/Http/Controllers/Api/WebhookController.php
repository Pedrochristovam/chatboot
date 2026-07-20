<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessMetaWebhookJob;
use Application\Services\WhatsApp\WhatsAppConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Infrastructure\Persistence\Eloquent\Models\WebhookReceipt;

class WebhookController extends Controller
{
    public function __construct(
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
        $rawPayload = $request->getContent();
        $appSecret = $this->whatsappConfig->metaAppSecret();
        $signatureValid = null;

        if (filled($appSecret)) {
            $expected = 'sha256='.hash_hmac('sha256', $rawPayload, $appSecret);
            $provided = (string) $request->header('X-Hub-Signature-256', '');
            $signatureValid = $provided !== '' && hash_equals($expected, $provided);

            if (! $signatureValid) {
                return response()->json(['message' => 'Invalid webhook signature.'], 401);
            }
        }

        $payload = $request->all();
        if (empty($payload)) {
            return response()->json(['status' => 'empty']);
        }

        $idempotencyKey = 'request:'.hash('sha256', $rawPayload);
        $now = now();

        DB::table('webhook_receipts')->insertOrIgnore([
            'provider' => 'meta',
            'idempotency_key' => $idempotencyKey,
            'event_type' => 'request',
            'payload' => json_encode($payload),
            'signature_valid' => $signatureValid === null ? null : ($signatureValid ? 1 : 0),
            'processing_status' => 'received',
            'attempts' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $receipt = WebhookReceipt::query()
            ->where('provider', 'meta')
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if (! $receipt) {
            return response()->json(['status' => 'accepted'], 202);
        }

        if (in_array($receipt->processing_status, ['processed', 'processing'], true)
            && $receipt->dispatched_at !== null) {
            return response()->json(['status' => 'duplicate']);
        }

        ProcessMetaWebhookJob::dispatch($receipt->id);
        $receipt->update([
            'dispatched_at' => $now,
            'signature_valid' => $signatureValid ?? $receipt->signature_valid,
        ]);

        return response()->json(['status' => 'queued'], 202);
    }
}
