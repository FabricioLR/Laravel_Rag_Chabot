<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Throwable;

class DashboardController extends Controller
{
    public function __construct(
        protected Dashboard $dashboardService
    ) {}

    public function index(Request $request): View
    {
        try {
            $metrics = $this->dashboardService->getSyncMetrics();
            $latestPosts = $this->dashboardService->getLatestIndexedPosts();
            $failedJobs = $this->dashboardService->getLatestFailedJobs();
            $metrics['latest_posts'] = $latestPosts;
            $metrics['failed_jobs'] = $failedJobs;
        } catch (Throwable $e) {
            $metrics = [
                'total_wordpress_posts' => 0,
                'indexed_posts_count'   => 0,
                'posts_remaining'       => 0,
                'sync_progress_percent' => 0.00,
                'latest_posts'          => [],
                'failed_jobs'           => [],
                'error'                 => $e->getMessage()
            ];
        }


        return view('admin.dashboard', $metrics);
    }
}