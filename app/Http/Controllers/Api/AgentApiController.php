<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\StoreAgentRequest;
use App\Http\Requests\Agent\UpdateAgentRequest;
use Application\Services\Agent\AgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Infrastructure\Persistence\Eloquent\Models\User;

class AgentApiController extends Controller
{
    public function __construct(private readonly AgentService $agentService) {}

    public function store(StoreAgentRequest $request): JsonResponse
    {
        $agent = $this->agentService->create($request->validated());

        return response()->json($agent, 201);
    }

    public function update(UpdateAgentRequest $request, User $user): JsonResponse
    {
        $agent = $this->agentService->update($user, $request->validated());

        return response()->json($agent);
    }

    public function destroy(User $user): JsonResponse
    {
        Gate::authorize('delete', $user);

        try {
            $this->agentService->delete($user);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => ['code' => 'agent_delete_failed', 'type' => 'domain'],
            ], 422);
        }

        return response()->json(['message' => 'Atendente removido.']);
    }
}
