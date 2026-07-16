<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Application\Services\Client\ClientService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function __construct(private readonly ClientService $clientService) {}

    public function index(Request $request): View
    {
        $paginator = $this->clientService->paginate($request->only(['search', 'status', 'tag_id']));
        $stats = $this->clientService->stats();
        $tags = $this->clientService->allTags();

        return view('clients.index', [
            'clients' => $paginator,
            'stats' => $stats,
            'tags' => $tags,
        ]);
    }
}
