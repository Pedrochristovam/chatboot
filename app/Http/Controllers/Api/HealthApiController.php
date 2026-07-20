<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Application\Services\Operations\HealthService;
use Illuminate\Http\JsonResponse;

class HealthApiController extends Controller
{
    public function __invoke(HealthService $health): JsonResponse
    {
        $snapshot = $health->snapshot();

        return response()->json($snapshot, $snapshot['ok'] ? 200 : 503);
    }
}
