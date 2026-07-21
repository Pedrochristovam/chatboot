<?php

use App\Http\Controllers\Web\AgentController;
use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\BotConversationController;
use App\Http\Controllers\Web\BotKnowledgeController;
use App\Http\Controllers\Web\ClientController;
use App\Http\Controllers\Web\ClosedConversationController;
use App\Http\Controllers\Web\ConversationController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\OperationsController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\ScheduledMessageController;
use App\Http\Controllers\Web\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');
    Route::get('/conversations', [ConversationController::class, 'index'])
        ->middleware('permission:conversations.view,conversations.manage')
        ->name('conversations.index');
    Route::get('/closed-conversations', [ClosedConversationController::class, 'index'])
        ->middleware('permission:conversations.view,conversations.manage')
        ->name('closed-conversations.index');
    Route::get('/bot-conversations', [BotConversationController::class, 'index'])
        ->middleware('permission:conversations.view,conversations.manage')
        ->name('bot-conversations.index');
    Route::get('/bot-knowledge', [BotKnowledgeController::class, 'index'])
        ->middleware('permission:bot.manage')
        ->name('bot-knowledge.index');
    Route::get('/clients', [ClientController::class, 'index'])
        ->middleware('permission:clients.manage')
        ->name('clients.index');
    Route::get('/agents', [AgentController::class, 'index'])
        ->middleware('permission:agents.manage')
        ->name('agents.index');
    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('permission:reports.view')
        ->name('reports.index');
    Route::get('/scheduled-messages', [ScheduledMessageController::class, 'index'])
        ->middleware('permission:conversations.manage')
        ->name('scheduled-messages.index');
    Route::get('/operations', [OperationsController::class, 'index'])
        ->middleware('permission:audit.view')
        ->name('operations.index');
    Route::post('/operations/messages/{message}/retry', [OperationsController::class, 'retry'])
        ->middleware('permission:audit.view')
        ->name('operations.messages.retry');
    Route::get('/settings', [SettingsController::class, 'index'])
        ->middleware('permission:settings.manage')
        ->name('settings.index');
});
