<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard;
use App\Services\DomainManager;
use App\Models\AllowedDomain;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use App\Models\ConversationHistory;
use Throwable;

class DashboardController extends Controller
{
    public function __construct(
        protected Dashboard $dashboardService,
        protected DomainManager $domainManager
    ) {}

    public function index(Request $request): View
    {
        try {
            $metrics = $this->dashboardService->getSyncMetrics();
            $metrics['latest_posts'] = $this->dashboardService->getLatestIndexedPosts();
            $metrics['unindexed_posts'] = $this->dashboardService->getLatestUnindexedPosts();
            $metrics['failed_jobs'] = $this->dashboardService->getLatestFailedJobs();

            $domains = AllowedDomain::orderBy('created_at', 'DESC')->get();
            $feedbacks = $this->dashboardService->getPaginatedFeedback(5);

            return view('admin.dashboard', array_merge($metrics, [
                'domains' => $domains,
                'feedbacks' => $feedbacks
            ]));
        } catch (Throwable $e) {
            $metrics = [
                'total_wordpress_posts' => 0,
                'indexed_posts_count'   => 0,
                'posts_remaining'       => 0,
                'latest_posts'          => [],
                'unindexed_posts'       => [],
                'failed_jobs'           => [],
                'error'                 => $e->getMessage()
            ];
            return view('admin.dashboard', array_merge($metrics, [
                'domains' => [],
                'feedbacks' => []
            ]));
        }
    }

    public function details($id)
    {
        $conversation = ConversationHistory::with('telemetry')->findOrFail($id);

        return view('admin.details', compact('conversation'));
    }

    public function storeDomain(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'required|unique:allowed_domains,domain'
        ]);

        try {
            $this->domainManager->register(
                $request->input('name'), 
                $request->input('domain')
            );
            return redirect()->back()->with('success', 'Domain added successfully!');
        } catch (Throwable $th) {
            return redirect()->back()->withErrors(['domain' => $th->getMessage()]);
        }
    }

    public function deleteDomain($id): RedirectResponse
    {
        try {
            $this->domainManager->revoke((int)$id);
            return redirect()->back()->with('success', 'Domain removed successfully!');
        } catch (Throwable $th) {
            return redirect()->back()->withErrors(['error' => $th->getMessage()]);
        }
    }
}