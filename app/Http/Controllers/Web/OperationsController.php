<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Application\Services\Messaging\FailedMessageService;
use Application\Services\Operations\HealthService;
use Domain\Shared\Enums\MessageStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Infrastructure\Persistence\Eloquent\Models\Message;

class OperationsController extends Controller
{
    public function index(HealthService $health): View
    {
        $failedMessages = Message::query()
            ->with(['conversation.client'])
            ->where('status', MessageStatus::Failed)
            ->latest()
            ->limit(100)
            ->get();

        return view('operations.index', [
            'health' => $health->snapshot(),
            'failedMessages' => $failedMessages,
        ]);
    }

    public function retry(Message $message, FailedMessageService $failedMessages): RedirectResponse
    {
        try {
            $failedMessages->retry($message);

            return back()->with('success', 'Mensagem recolocada na fila.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }
}
