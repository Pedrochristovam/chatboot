<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Application\Services\Report\ReportService;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    public function index(): View
    {
        $metrics = $this->reportService->getMetrics();
        $dailyChart = $this->reportService->dailyChart();
        $messagesByAgent = $this->reportService->messagesByAgent();
        $departments = $this->reportService->departmentPerformance();

        return view('reports.index', compact('metrics', 'dailyChart', 'messagesByAgent', 'departments'));
    }
}
