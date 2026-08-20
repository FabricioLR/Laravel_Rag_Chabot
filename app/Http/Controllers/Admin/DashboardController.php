<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientToken;
use App\Models\ConversationHistory;
use App\Models\HelpRequest;
use App\Services\Dashboard;
use App\Services\DomainManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class DashboardController extends Controller
{
    public function __construct(
        protected Dashboard $dashboardService,
        protected DomainManager $domainManager
    ) {}

    public function index(Request $request): View
    {
        $search = $request->input('search');

        try {
            $data = array_merge(
                $this->dashboardService->getSyncMetrics(),
                $this->dashboardService->getPipelineMetrics(),
                [
                    'latest_posts'        => $this->dashboardService->getLatestIndexedPosts(),
                    'unindexed_posts'     => $this->dashboardService->getLatestUnindexedPosts(),
                    'failed_jobs'         => $this->dashboardService->getLatestFailedJobs(),
                    'tokens'              => ClientToken::with('allowedDomains')->orderBy('created_at', 'DESC')->get(),
                    'feedbacks'           => $this->dashboardService->getPaginatedFeedback(5, $search),
                    'requests_per_domain' => $this->dashboardService->getRequestsPerDomain(),
                    'help' => HelpRequest::orderBy('created_at', 'DESC')->limit(5)->get(),
                ]
            );
        } catch (Throwable $e) {
            $data = [
                'total_wordpress_posts' => 0,
                'indexed_posts_count'   => 0,
                'posts_remaining'       => 0,
                'latest_posts'          => [],
                'unindexed_posts'       => [],
                'failed_jobs'           => [],
                'tokens'                => [],
                'feedbacks'             => [],
                'requests_per_domain'   => [],
                'help'                  => [],
                'error'                 => $e->getMessage(),
            ];
        }

        return view('admin.dashboard', $data);
    }

    public function details($id): View
    {
        $conversation = ConversationHistory::with('telemetry')->findOrFail($id);

        Log::debug("CONVERSATION DATA: ", ['data' => $conversation]);

        return view('admin.details', compact('conversation'));
    }

    public function storeToken(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            $this->domainManager->createToken($request->input('name'));

            return redirect()->back()->with('success', 'Client token created successfully!');
        } catch (Throwable $th) {
            return redirect()->back()->withErrors(['token' => $th->getMessage()]);
        }
    }

    public function deleteToken($id): RedirectResponse
    {
        try {
            $token = ClientToken::findOrFail((int) $id);
            $token->delete();

            return redirect()->back()->with('success', 'Client token and associated domains deleted!');
        } catch (Throwable $th) {
            return redirect()->back()->withErrors(['error' => $th->getMessage()]);
        }
    }

    public function storeDomain(Request $request): RedirectResponse
    {
        $request->validate([
            'client_token_id' => 'required|exists:client_tokens,id',
            'domain'          => 'required|string',
        ]);

        try {
            $this->domainManager->addDomain(
                (int) $request->input('client_token_id'),
                $request->input('domain')
            );

            return redirect()->back()->with('success', 'Domain attached to token successfully!');
        } catch (Throwable $th) {
            return redirect()->back()->withErrors(['domain' => $th->getMessage()]);
        }
    }

    public function deleteDomain($id): RedirectResponse
    {
        try {
            $this->domainManager->revokeDomain((int) $id);

            return redirect()->back()->with('success', 'Domain removed successfully!');
        } catch (Throwable $th) {
            return redirect()->back()->withErrors(['error' => $th->getMessage()]);
        }
    }

    public function updateToken(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'name'    => 'required|string',
        ]);

        try {
            $this->domainManager->updateToken(
                (int) $id,
                $request->input('name')
            );

            return redirect()->back()->with('success', 'Client token updated successfully!');
        } catch (Throwable $th) {
            return redirect()->back()->withErrors(['domain' => $th->getMessage()]);
        }
    }
}