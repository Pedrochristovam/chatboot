<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Application\Services\Settings\SettingsService;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private readonly SettingsService $settingsService) {}

    public function index(): View
    {
        return view('settings.index', [
            'settings' => $this->settingsService->all(),
        ]);
    }
}
