<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Http\Requests\Message\SendMessageRequest;
use Application\Services\Client\ClientService;
use Application\Services\Conversation\ConversationService;
use Application\Services\Conversation\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Infrastructure\Persistence\Eloquent\Models\Client;
use Infrastructure\Persistence\Eloquent\Models\Conversation;

class ClientApiController extends Controller
{
    public function __construct(private readonly ClientService $clientService) {}

    public function index(Request $request): JsonResponse
    {
        $clients = $this->clientService->paginate($request->only(['search', 'status', 'tag_id']));

        return response()->json($clients);
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = $this->clientService->create($request->validated());

        return response()->json($client->load('tags'), 201);
    }

    public function show(Client $client): JsonResponse
    {
        return response()->json($client->load('tags'));
    }

    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        $client = $this->clientService->update($client, $request->validated());

        return response()->json($client);
    }

    public function destroy(Client $client): JsonResponse
    {
        $this->clientService->delete($client);

        return response()->json(['message' => 'Cliente removido.']);
    }
}
