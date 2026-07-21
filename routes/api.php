<?php

use App\Http\Controllers\Api\AgentApiController;
use App\Http\Controllers\Api\BotKnowledgeApiController;
use App\Http\Controllers\Api\ClientApiController;
use App\Http\Controllers\Api\ConversationApiController;
use App\Http\Controllers\Api\FailedMessageApiController;
use App\Http\Controllers\Api\HealthApiController;
use App\Http\Controllers\Api\PresenceApiController;
use App\Http\Controllers\Api\SettingsApiController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/whatsapp', [WebhookController::class, 'receive'])->middleware('throttle:120,1');
Route::get('/webhook/whatsapp', [WebhookController::class, 'verify'])->middleware('throttle:60,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());
});

Route::middleware(['web', 'auth', 'throttle:120,1'])->prefix('internal')->group(function () {
    Route::post('presence/heartbeat', [PresenceApiController::class, 'heartbeat'])->name('api.presence.heartbeat');
    Route::post('presence/offline', [PresenceApiController::class, 'offline'])->name('api.presence.offline');
    Route::get('health', HealthApiController::class)->middleware('permission:audit.view')->name('api.health');
    Route::post('messages/{message}/retry', [FailedMessageApiController::class, 'retry'])
        ->middleware('permission:audit.view')
        ->name('api.messages.retry');
    Route::apiResource('clients', ClientApiController::class)->names('api.clients');
    Route::get('conversations', [ConversationApiController::class, 'index']);
    Route::get('conversations/lookup', [ConversationApiController::class, 'lookup']);
    Route::get('conversations/{conversation}', [ConversationApiController::class, 'show']);
    Route::get('conversations/{conversation}/card', [ConversationApiController::class, 'card']);
    Route::post('conversations/{conversation}/messages', [ConversationApiController::class, 'sendMessage']);
    Route::post('conversations/{conversation}/templates', [ConversationApiController::class, 'sendTemplate']);
    Route::post('conversations/{conversation}/close', [ConversationApiController::class, 'close']);
    Route::post('conversations/{conversation}/assign', [ConversationApiController::class, 'assign']);
    Route::post('conversations/{conversation}/transfer', [ConversationApiController::class, 'transfer']);
    Route::get('conversations/{conversation}/notes', [ConversationApiController::class, 'notes']);
    Route::post('conversations/{conversation}/notes', [ConversationApiController::class, 'storeNote']);
    Route::post('conversations/{conversation}/scheduled-messages', [ConversationApiController::class, 'storeScheduled']);
    Route::delete('scheduled-messages/{scheduled}', [ConversationApiController::class, 'cancelScheduled']);
    Route::delete('conversation-notes/{note}', [ConversationApiController::class, 'destroyNote']);
    Route::post('bot/simulate', [ConversationApiController::class, 'simulateInbound']);
    Route::post('agents', [AgentApiController::class, 'store']);
    Route::put('agents/{user}', [AgentApiController::class, 'update']);
    Route::delete('agents/{user}', [AgentApiController::class, 'destroy']);
    Route::put('settings', [SettingsApiController::class, 'update']);

    Route::get('bot-knowledge', [BotKnowledgeApiController::class, 'index']);
    Route::put('bot-knowledge/ask-name', [BotKnowledgeApiController::class, 'updateAskName']);
    Route::post('bot-topics', [BotKnowledgeApiController::class, 'storeTopic']);
    Route::put('bot-topics/{topic}', [BotKnowledgeApiController::class, 'updateTopic']);
    Route::delete('bot-topics/{topic}', [BotKnowledgeApiController::class, 'destroyTopic']);
    Route::post('bot-knowledge-items', [BotKnowledgeApiController::class, 'storeKnowledge']);
    Route::put('bot-knowledge-items/{knowledge}', [BotKnowledgeApiController::class, 'updateKnowledge']);
    Route::delete('bot-knowledge-items/{knowledge}', [BotKnowledgeApiController::class, 'destroyKnowledge']);
});
