<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Application\Services\Dashboard\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function index(): View
    {
        return view('dashboard.index', [
            'metrics' => $this->dashboardService->getMetrics(),
            'attendanceChart' => $this->dashboardService->getAttendanceChart(),
            'monthlyChart' => $this->dashboardService->getMonthlyChart(),
            'recentConversations' => $this->dashboardService->getRecentConversations(),
        ]);
    }
}
