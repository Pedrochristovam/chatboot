<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Application\Services\Messaging\FailedMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Infrastructure\Persistence\Eloquent\Models\Message;

class FailedMessageApiController extends Controller
{
    public function retry(Message $message, FailedMessageService $failedMessages): JsonResponse
    {
        Gate::authorize('sendMessage', $message->conversation);

        try {
            $message = $failedMessages->retry($message);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => ['code' => 'message_retry_failed', 'type' => 'domain'],
            ], 422);
        }

        return response()->json([
            'message' => 'Mensagem recolocada na fila.',
            'data' => ['id' => $message->id, 'status' => $message->status->value],
        ]);
    }
}
