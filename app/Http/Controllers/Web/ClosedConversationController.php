<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ClosedConversationController extends Controller
{
    public function index(): View
    {
        return view('closed-conversations.index', [
            'agentId' => auth()->id(),
            'agentName' => auth()->user()?->name,
        ]);
    }
}
