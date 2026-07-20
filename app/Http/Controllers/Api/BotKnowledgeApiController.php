<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\ManageBotRequest;
use Application\Services\Bot\BotKnowledgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Infrastructure\Persistence\Eloquent\Models\BotKnowledge;
use Infrastructure\Persistence\Eloquent\Models\BotTopic;

class BotKnowledgeApiController extends Controller
{
    public function __construct(private readonly BotKnowledgeService $service) {}

    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', BotKnowledge::class);

        return response()->json([
            'topics' => $this->service->topicsPayload(),
            'stats' => $this->service->stats(),
            'ask_name_message' => $this->service->askNameMessage(),
            'welcome_back_message' => $this->service->welcomeBackMessage(),
        ]);
    }

    public function updateAskName(ManageBotRequest $request): JsonResponse
    {
        $data = $request->validate([
            'ask_name_message' => ['required', 'string', 'max:1000'],
            'welcome_back_message' => ['required', 'string', 'max:1000'],
        ]);

        $this->service->updateAskNameMessage($data['ask_name_message']);
        $this->service->updateWelcomeBackMessage($data['welcome_back_message']);

        return response()->json(['ok' => true]);
    }

    public function storeTopic(ManageBotRequest $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'transfers_to_human' => ['sometimes', 'boolean'],
        ]);

        $topic = $this->service->createTopic($data);

        return response()->json(['topic' => $this->service->serializeTopic($topic)], 201);
    }

    public function updateTopic(ManageBotRequest $request, BotTopic $topic): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'transfers_to_human' => ['sometimes', 'boolean'],
        ]);

        $topic = $this->service->updateTopic($topic, $data);

        return response()->json(['topic' => $this->service->serializeTopic($topic)]);
    }

    public function destroyTopic(BotTopic $topic): JsonResponse
    {
        Gate::authorize('delete', $topic);

        $this->service->deleteTopic($topic);

        return response()->json(['ok' => true]);
    }

    public function storeKnowledge(ManageBotRequest $request): JsonResponse
    {
        $data = $request->validate([
            'bot_topic_id' => ['required', 'exists:bot_topics,id'],
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:4000'],
            'keywords' => ['nullable'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $item = $this->service->createKnowledge($data);

        return response()->json(['knowledge' => $this->service->serializeKnowledge($item)], 201);
    }

    public function updateKnowledge(ManageBotRequest $request, BotKnowledge $knowledge): JsonResponse
    {
        $data = $request->validate([
            'bot_topic_id' => ['sometimes', 'exists:bot_topics,id'],
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:4000'],
            'keywords' => ['nullable'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $item = $this->service->updateKnowledge($knowledge, $data);

        return response()->json(['knowledge' => $this->service->serializeKnowledge($item)]);
    }

    public function destroyKnowledge(BotKnowledge $knowledge): JsonResponse
    {
        Gate::authorize('delete', $knowledge);

        $this->service->deleteKnowledge($knowledge);

        return response()->json(['ok' => true]);
    }
}
