<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Application\Services\Agent\AgentService;
use Illuminate\View\View;

class AgentController extends Controller
{
    public function __construct(private readonly AgentService $agentService) {}

    public function index(): View
    {
        return view('agents.index', [
            'agents' => $this->agentService->all(),
            'stats' => $this->agentService->stats(),
            'roles' => $this->agentService->roles(),
            'departments' => \Infrastructure\Persistence\Eloquent\Models\Department::query()->where('is_active', true)->get(),
        ]);
    }
}
