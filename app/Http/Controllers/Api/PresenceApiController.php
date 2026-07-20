<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Application\Services\Conversation\AgentPresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PresenceApiController extends Controller
{
    public function heartbeat(Request $request, AgentPresenceService $presence): JsonResponse
    {
        $presence->heartbeat($request->user());

        return response()->json([
            'ok' => true,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function offline(Request $request, AgentPresenceService $presence): JsonResponse
    {
        $requeued = $presence->markOffline($request->user(), 'client_offline');

        return response()->json(['ok' => true, 'requeued' => $requeued]);
    }
}
