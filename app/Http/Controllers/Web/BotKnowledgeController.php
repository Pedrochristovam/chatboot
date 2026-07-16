<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Application\Services\Bot\BotKnowledgeService;
use Illuminate\View\View;

class BotKnowledgeController extends Controller
{
    public function __construct(private readonly BotKnowledgeService $botKnowledgeService) {}

    public function index(): View
    {
        return view('bot-knowledge.index', [
            'initial' => [
                'topics' => $this->botKnowledgeService->topicsPayload(),
                'stats' => $this->botKnowledgeService->stats(),
                'ask_name_message' => $this->botKnowledgeService->askNameMessage(),
                'welcome_back_message' => $this->botKnowledgeService->welcomeBackMessage(),
            ],
        ]);
    }
}
