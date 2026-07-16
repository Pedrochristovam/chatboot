<?php

use App\Http\Controllers\Web\AgentController;
use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\BotConversationController;
use App\Http\Controllers\Web\BotKnowledgeController;
use App\Http\Controllers\Web\ClientController;
use App\Http\Controllers\Web\ClosedConversationController;
use App\Http\Controllers\Web\ConversationController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::get('/closed-conversations', [ClosedConversationController::class, 'index'])->name('closed-conversations.index');
    Route::get('/bot-conversations', [BotConversationController::class, 'index'])->name('bot-conversations.index');
    Route::get('/bot-knowledge', [BotKnowledgeController::class, 'index'])->name('bot-knowledge.index');
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('/agents', [AgentController::class, 'index'])->name('agents.index');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
});
