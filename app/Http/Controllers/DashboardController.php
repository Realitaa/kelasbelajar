<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminDashboardRequest;
use App\Services\DashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Render the admin dashboard with system statistics.
     */
    public function index(AdminDashboardRequest $request, DashboardService $dashboardService): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => $dashboardService->getAdminStats(),
            'recentUsers' => $dashboardService->getRecentUsers(),
            'recentClassrooms' => $dashboardService->getRecentClassrooms(),
        ]);
    }
}
