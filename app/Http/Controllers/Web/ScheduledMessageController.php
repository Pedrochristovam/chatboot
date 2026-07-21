<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Application\Services\Messaging\ScheduledMessageService;
use Illuminate\View\View;

class ScheduledMessageController extends Controller
{
    public function index(ScheduledMessageService $scheduledMessages): View
    {
        return view('scheduled-messages.index', [
            'items' => $scheduledMessages->listRecent(150),
        ]);
    }
}
