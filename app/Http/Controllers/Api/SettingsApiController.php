<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use Application\Services\Settings\SettingsService;
use Illuminate\Http\JsonResponse;

class SettingsApiController extends Controller
{
    public function __construct(private readonly SettingsService $settingsService) {}

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $this->settingsService->update($request->validated());

        return response()->json([
            'message' => 'Configurações salvas com sucesso.',
            'settings' => $this->settingsService->all(),
        ]);
    }
}
