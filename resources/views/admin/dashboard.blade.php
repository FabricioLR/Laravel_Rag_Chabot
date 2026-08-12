<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WordPress & Pipeline Metrics Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800" x-data="{ refreshing: false }">
    <div class="fixed top-5 right-5 z-50 space-y-3 max-w-sm w-full pointer-events-none">
        @if(isset($error) || $errors->any())
            <div x-data="{ show: true }" 
                 x-show="show" 
                 x-init="setTimeout(() => show = false, 5000)"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-x-2"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-x-0"
                 x-transition:leave-end="opacity-0 translate-x-2"
                 class="pointer-events-auto bg-slate-800 border-l-4 border-rose-500 p-4 rounded-lg shadow-2xl flex items-start space-x-3 border border-slate-700">
                <svg class="w-5 h-5 flex-shrink-0 text-rose-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-slate-200">Warning: Action encountered errors.</p>
                    <p class="text-xs text-rose-400 mt-0.5">{{ $error ?? $errors->first() }}</p>
                </div>
                <button @click="show = false" class="text-slate-400 hover:text-slate-200"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
        @endif
        
        @if (session('success'))
            <div x-data="{ show: true }" 
                 x-show="show" 
                 x-init="setTimeout(() => show = false, 5000)"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-x-2"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-x-0"
                 x-transition:leave-end="opacity-0 translate-x-2"
                 class="pointer-events-auto bg-slate-800 border-l-4 border-emerald-500 p-4 rounded-lg shadow-2xl flex items-start space-x-3 border border-slate-700">
                <svg class="w-5 h-5 flex-shrink-0 text-emerald-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-slate-200">Success</p>
                    <p class="text-xs text-emerald-400 mt-0.5">{{ session('success') }}</p>
                </div>
                <button @click="show = false" class="text-slate-400 hover:text-slate-200"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
        @endif

    </div>

    <div class="min-h-screen flex flex-col">
        <!-- Top Navigation -->
        <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center space-x-3">
                        <span class="text-xl font-bold text-gray-800">Control Panel</span>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        @if(Auth::check())
                            <span class="text-sm text-gray-600 hidden sm:inline">
                                Logged in as: <strong class="text-gray-800">{{ Auth::user()->name }}</strong>
                            </span>
                            
                            <form action="{{ route('admin.logout') }}" method="POST" class="inline m-0">
                                @csrf
                                <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-500 bg-transparent border-0 cursor-pointer p-0">
                                    Logout
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </nav>

        <main class="flex-1 max-w-7xl w-full mx-auto p-6 sm:p-8">

            <!-- Header Section with Actions -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between pb-6 border-b border-gray-200 gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900">Metrics</h1>
                    <p class="text-sm text-gray-500 mt-1">LLM query execution latency, token utilization, and pipeline status.</p>
                </div>
            </div>

            <!-- Error Banner -->
            @if(isset($error))
                <div class="mt-6 bg-red-50 border-l-4 border-red-500 p-4 rounded shadow-sm">
                    <p class="text-sm text-red-700 font-semibold">Warning: Core aggregation pipeline encountered errors.</p>
                    <p class="text-xs text-red-600 mt-1">{{ $error }}</p>
                </div>
            @endif

            <!-- 1. LLM & Execution Latency Metrics -->
            <div class="mt-6">
                <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">LLM Execution Performance & Token Usage</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    
                    <!-- Avg Duration (MS / Seconds) -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                        <div class="flex justify-between items-start text-gray-400">
                            <span class="text-xs font-semibold uppercase tracking-wider">Avg Latency</span>
                        </div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-3xl font-bold text-emerald-600">
                                {{ isset($avg_duration_ms) ? number_format($avg_duration_ms, 0) : '0' }} <span class="text-lg font-normal text-gray-500">ms</span>
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            ≈ {{ isset($avg_duration_seconds) ? number_format($avg_duration_seconds, 2) : (isset($avg_duration_ms) ? number_format($avg_duration_ms / 1000, 2) : '0.00') }} sec per request
                        </p>
                    </div>

                    <!-- Avg Input Tokens -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                        <div class="flex justify-between items-start text-gray-400">
                            <span class="text-xs font-semibold uppercase tracking-wider">Avg Input Tokens</span>
                        </div>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($avg_input_tokens ?? 0) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Prompt context depth</p>
                    </div>

                    <!-- Avg Output Tokens -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                        <div class="flex justify-between items-start text-gray-400">
                            <span class="text-xs font-semibold uppercase tracking-wider">Avg Output Tokens</span>
                        </div>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($avg_output_tokens ?? 0) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Generated answer length</p>
                    </div>

                    <!-- Total Token Volume -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                        <div class="flex justify-between items-start text-gray-400">
                            <span class="text-xs font-semibold uppercase tracking-wider">AVG Total Tokens</span>
                        </div>
                        <p class="text-3xl font-bold text-indigo-600 mt-2">{{ number_format($avg_total_tokens ?? 0) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Combined input + output</p>
                    </div>

                </div>
            </div>

            <!-- 2. WordPress Sync Overview Cards -->
            <div class="mt-8">
                <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">WordPress Synchronization Status</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Total WordPress Posts -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                        <div class="flex justify-between items-start text-gray-400">
                            <span class="text-xs font-semibold uppercase tracking-wider">Total WP Posts</span>
                        </div>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($total_wordpress_posts ?? 0) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Source total</p>
                    </div>

                    <!-- Indexed Posts (pgvector) -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                        <div class="flex justify-between items-start text-gray-400">
                            <span class="text-xs font-semibold uppercase tracking-wider">Indexed</span>
                        </div>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($indexed_posts_count ?? 0) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Ready for vector search</p>
                    </div>

                    <!-- Unindexed Posts Remaining -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                        <div class="flex justify-between items-start text-gray-400">
                            <span class="text-xs font-semibold uppercase tracking-wider">Queue Backlog</span>
                        </div>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($posts_remaining ?? 0) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Awaiting vectorization</p>
                    </div>

                    <!-- Sync Success / Pipeline Rate -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                        <div class="flex justify-between items-start text-gray-400">
                            <span class="text-xs font-semibold uppercase tracking-wider">Sync Completeness</span>
                        </div>
                        <div class="mt-2 flex items-baseline gap-2">
                            @php
                                $rate = ($total_wordpress_posts ?? 0) > 0 
                                    ? round((($indexed_posts_count ?? 0) / $total_wordpress_posts) * 100, 1) 
                                    : 0;
                            @endphp
                            <span class="text-3xl font-bold text-gray-900">{{ $rate }}%</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Target benchmark: 100%</p>
                    </div>
                </div>
            </div>

            <!-- Recently Indexed Posts -->
            <div class="mt-12" x-data="{ open: false }">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-900">Indexed Posts</h2>
                    <button @click="open = !open" class="text-xs font-semibold text-gray-500 hover:text-gray-700 bg-white border border-gray-300 rounded-md px-3 py-1.5 shadow-sm transition-colors cursor-pointer">
                        <span x-text="open ? 'Hide Table' : 'Show Table'"></span>
                    </button>
                </div>
                <div x-show="open" x-transition class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">WP ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Published Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Indexed Date</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($latest_posts as $post)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500">#{{ $post->ID }}</td>
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900 max-w-md truncate">{{ $post->post_title }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($post->post_date)->format('M d, Y H:i') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $post->indexed_at ? \Carbon\Carbon::parse($post->indexed_at)->format('M d, Y H:i') : 'Not indexed' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Indexed</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">No recently indexed posts found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pending Indexing Table -->
            <div class="mt-12" x-data="{ open: false }">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-900">Awaiting Indexing</h2>
                    <button @click="open = !open" class="text-xs font-semibold text-gray-500 hover:text-gray-700 bg-white border border-gray-300 rounded-md px-3 py-1.5 shadow-sm transition-colors cursor-pointer">
                        <span x-text="open ? 'Hide Table' : 'Show Table'"></span>
                    </button>
                </div>
                <div x-show="open" x-transition class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">WP ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Published Date</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($unindexed_posts as $post)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500">#{{ $post->ID }}</td>
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900 max-w-md truncate">{{ $post->post_title }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($post->post_date)->format('M d, Y H:i') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Pending Sync</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500">All published content is fully synchronized and up to date!</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Ingestion Failures Table -->
            <div class="mt-12" x-data="{ open: false }">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-900">Ingestion Failures</h2>
                    <button @click="open = !open" class="text-xs font-semibold text-gray-500 hover:text-gray-700 bg-white border border-gray-300 rounded-md px-3 py-1.5 shadow-sm transition-colors cursor-pointer">
                        <span x-text="open ? 'Hide Table' : 'Show Table'"></span>
                    </button>
                </div>
                <div x-show="open" x-transition class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">WP ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target Post</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason / Exception Context</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Failed At</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($failed_jobs as $job)
                                    <tr class="hover:bg-red-50/40 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500">{{ !empty($job['post_id']) ? '#' . $job['post_id'] : 'N/A' }}</td>
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900 max-w-xs truncate">{{ $job['title'] ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 text-xs font-mono text-red-600 max-w-md break-words bg-red-50/30">{{ $job['error'] ?? 'Unknown Error' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ isset($job['failed_at']) ? \Carbon\Carbon::parse($job['failed_at'])->diffForHumans() : 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-400">No ingestion failures found in the queue pipeline.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Feedback Table -->
            <div class="mt-12" x-data="{ open: true }">
                <!-- Header Block -->
                <div class="mb-4">
                    <!-- Title and Toggle Button Row -->
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-bold text-gray-900">Conversation Logs</h2>
                        <button @click="open = !open" class="text-xs font-semibold text-gray-500 hover:text-gray-700 bg-white border border-gray-300 rounded-md px-3 py-1.5 shadow-sm transition-colors cursor-pointer">
                            <span x-text="open ? 'Hide Table' : 'Show Table'"></span>
                        </button>
                    </div>

                    <!-- Full-Text Search Input (Placed directly at the bottom of the title) -->
                    <div x-show="open" x-transition class="mt-3">
                        <form method="GET" action="{{ url()->current() }}" class="relative max-w-md w-full">
                            {{-- Preserve existing query string params (e.g. filters/tabs) --}}
                            @foreach(request()->except(['search', 'page']) as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach

                            <div class="relative">
                                <input 
                                    type="text" 
                                    name="search" 
                                    value="{{ request('search') }}" 
                                    placeholder="Search logs by session, question, or answer..." 
                                    class="w-full pl-9 pr-8 py-2 text-xs bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-900 placeholder-gray-400"
                                />
                                <!-- Search Icon -->
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>

                                <!-- Clear Button (Visible when searching) -->
                                @if(request('search'))
                                    <a href="{{ url()->current() }}" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-gray-400 hover:text-gray-600" title="Clear search">
                                        <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Table Container -->
                <div x-show="open" x-transition class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Session Reference</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Question</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bot Message Answer Output</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">User Evaluation</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Logged At</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($feedbacks as $feedback)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-xs font-mono text-gray-500 max-w-[140px]">
                                            <div class="flex items-center gap-2 group">
                                                <a href="{{ route('admin.details', $feedback->id) }}" class="truncate cursor-pointer text-indigo-600 hover:text-indigo-900 font-semibold hover:underline" title="View detailed LLM request & prompt parameters">
                                                    {{ $feedback->session_id }}
                                                </a>
                                                <button type="button" 
                                                        onclick="copySessionId('{{ $feedback->session_id }}', this)" 
                                                        class="text-gray-400 hover:text-indigo-600 focus:outline-none opacity-0 group-hover:opacity-100 transition-opacity"
                                                        title="Copy Session ID">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700 max-w-xs break-words" title="{{ $feedback->question }}">
                                            {{ Str::limit($feedback->question, 90, '...') }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600 max-w-sm break-words" title="{{ $feedback->answer }}">
                                            {{ Str::limit($feedback->answer, 120, '...') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                            @if(strtolower($feedback->feedback) === 'positive')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-700 border border-green-200">
                                                    Helpful
                                                </span>
                                            @elseif(strtolower($feedback->feedback) === 'negative')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                                                    Unhelpful
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-50 text-gray-700 border border-gray-200">
                                                    None
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                                            {{ $feedback->created_at ? \Carbon\Carbon::parse($feedback->created_at)->diffForHumans() : 'N/A' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-400">
                                            @if(request('search'))
                                                No logs matching "{{ request('search') }}" found.
                                            @else
                                                No widget rating submissions received yet.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(method_exists($feedbacks, 'hasPages') && $feedbacks->hasPages())
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                            {{ $feedbacks->appends(request()->query())->onEachSide(1)->links() }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Requests Per Domain Table (Last 7 Days) -->
            <div class="mt-12" x-data="{ open: true }">
                <div class="flex justify-between items-center mb-4">
                    <div class="flex items-center gap-3">
                        <h2 class="text-xl font-bold text-gray-900">Requests Per Domain</h2>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                            Past 7 Days
                        </span>
                    </div>
                    <button @click="open = !open" class="text-xs font-semibold text-gray-500 hover:text-gray-700 bg-white border border-gray-300 rounded-md px-3 py-1.5 shadow-sm transition-colors cursor-pointer">
                        <span x-text="open ? 'Hide Table' : 'Show Table'"></span>
                    </button>
                </div>
                
                <div x-show="open" x-transition class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Domain</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Requests</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($requests_per_domain as $row)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $row->origin ?? 'Unknown' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-gray-700">
                                            {{ number_format($row->total_requests) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="px-6 py-12 text-center text-sm text-gray-400">
                                            No domain telemetry recorded in the past 7 days.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Origin Management & Embedding -->
            <div class="mt-12 grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Management Forms -->
                <div class="space-y-6 h-fit">
                    <!-- Form 1: Create New Client Token -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Create Client Token</h2>
                        <form action="{{ route('admin.tokens.store') }}" method="POST">
                            @csrf
                            <div class="mb-4">
                                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Client Name</label>
                                <input type="text" name="name" required placeholder="e.g. Acme Corp Portal" class="w-full text-sm border-gray-300 rounded-md bg-gray-50 p-2.5 focus:outline-emerald-500 border">
                            </div>
                            <button type="submit" class="w-full text-sm bg-gray-800 hover:bg-gray-900 text-white font-medium py-2.5 px-4 rounded transition-colors shadow-sm">
                                Generate Client Token
                            </button>
                        </form>
                    </div>

                    <!-- Form 2: Register New Origin Domain -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Register Origin Domain</h2>
                        <form action="{{ route('admin.domains.store') }}" method="POST">
                            @csrf
                            <div class="mb-4">
                                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Select Client Token</label>
                                <select name="client_token_id" required class="w-full text-sm border-gray-300 rounded-md bg-gray-50 p-2.5 focus:outline-emerald-500 border">
                                    <option value="" disabled selected>-- Select an Active Token --</option>
                                    @foreach($tokens as $token)
                                        <option value="{{ $token->id }}">{{ $token->name }} ({{ Str::limit($token->token, 12) }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Origin URL / Pattern</label>
                                <input type="url" name="domain" required placeholder="https://app.example.com" class="w-full text-sm border-gray-300 rounded-md bg-gray-50 p-2.5 focus:outline-emerald-500 border">
                            </div>
                            <button type="submit" class="w-full text-sm bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2.5 px-4 rounded transition-colors shadow-sm">
                                Attach Domain to Token
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Table Listing Tokens and Associated Domains -->
                <div class="lg:col-span-2 bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/50 flex justify-between items-center">
                        <h2 class="text-lg font-bold text-gray-900">Authorized Domains</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client Token</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Allowed Domains</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Snippet</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Manage</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($tokens as $token)
                                    <tr class="hover:bg-gray-50/60 transition-colors align-top">
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                            <div>{{ $token->name }}</div>
                                            <div class="text-xs font-mono text-gray-400 mt-0.5">{{ Str::limit($token->token, 16) }}</div>
                                            @if(!$token->is_active)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 mt-1">Disabled Token</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm font-mono text-gray-500">
                                            @if($token->allowedDomains->isEmpty())
                                                <span class="text-xs text-gray-400 italic">No domains assigned</span>
                                            @else
                                                <ul class="space-y-2">
                                                    @foreach($token->allowedDomains as $dom)
                                                        <li class="flex items-center justify-between group">
                                                            <span title="{{  $dom->domain }}">
                                                                {{  Str::limit($dom->domain, 20) }}
                                                                @if(!$dom->is_active)
                                                                    <span class="ml-1 text-xs text-red-600">(Disabled)</span>
                                                                @endif
                                                            </span>
                                                            <form action="{{ route('admin.domains.delete', $dom->id) }}" method="POST" class="inline-block ml-2" onsubmit="return confirm('Revoke this origin domain?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="text-xs text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity">Remove</button>
                                                            </form>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <button onclick="openSnippetModal('{{ addslashes($token->name) }}', '{{ $token->token }}')" 
                                                    class="inline-flex items-center text-xs bg-gray-100 hover:bg-emerald-50 hover:text-emerald-700 text-gray-700 font-medium py-1.5 px-3 rounded-md transition-all border border-gray-200 cursor-pointer">
                                                Code Snippet
                                            </button>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-3">
                                            <button 
                                                type="button" 
                                                onclick="openEditTokenModal('{{ $token->id }}', '{{ addslashes($token->name) }}')" 
                                                class="text-indigo-600 hover:text-indigo-900 text-xs font-semibold cursor-pointer"
                                            >
                                                Edit
                                            </button>
                                            <form action="{{ route('admin.tokens.delete', $token->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Revoking this token will disconnect all its assigned domains. Continue?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-semibold cursor-pointer">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-400">No client tokens or origins registered yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Help Requests Section -->
            <div class="mt-8 bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/50 flex justify-between items-center">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Recent Support Requests</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Latest 5 messages received via the help form</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ count($help ?? []) }} Messages
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User / Company</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Received At</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($help as $item)
                                <tr class="hover:bg-gray-50/60 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="font-semibold">{{ $item->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $item->company_name }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 max-w-md break-words" title="{{ $item->description }}">
                                        {{ Str::limit($item->description, 120) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-xs text-gray-400">
                                        {{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '-' }}
                                        <div class="text-[10px] text-gray-400 mt-0.5">{{ $item->created_at ? $item->created_at->diffForHumans() : '' }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-400">
                                        No help messages recorded yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="editTokenModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <h3 class="text-base font-bold text-gray-900">Edit Client Token</h3>
                        <button type="button" onclick="closeEditTokenModal()" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                    </div>
                    
                    <form id="editTokenForm" method="POST" action="">
                        @csrf
                        @method('PUT')
                        
                        <div class="p-6 space-y-4">
                            <div>
                                <label for="edit_token_name" class="block text-xs font-medium text-gray-700 uppercase">Client Name</label>
                                <input type="text" name="name" id="edit_token_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                            </div>
                        </div>

                        <div class="px-6 py-3 bg-gray-50 text-right space-x-2">
                            <button type="button" onclick="closeEditTokenModal()" class="px-4 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                            <button type="submit" class="px-4 py-2 text-xs font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 shadow-sm">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Snippet Code Modal -->
            <div 
                id="snippetModal" 
                class="fixed inset-0 z-50 min-w-full min-h-screen bg-gray-900/60 backdrop-blur-sm hidden flex-col justify-center items-center p-4"
            >
                <div class="bg-white max-w-2xl w-full rounded-xl shadow-xl overflow-hidden border border-gray-100 animate-in fade-in zoom-in-95 duration-150 mx-auto my-auto">
                    
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                        <h3 id="modalTitle" class="text-base font-bold text-gray-900">Embedded Snippet Config</h3>
                        <button onclick="closeSnippetModal()" class="text-gray-400 hover:text-gray-600 text-xl font-bold leading-none cursor-pointer">&times;</button>
                    </div>
                    
                    <div class="p-6">
                        <p class="text-sm text-gray-600 mb-3">
                            Instruct your client to paste this HTML/JS integration payload block inside their global web layout file right before closing the trailing 
                            <code class="font-mono bg-gray-100 text-xs p-0.5 rounded">&lt;/body&gt;</code> element block:
                        </p>
                        
                        <div class="relative w-full">
                            <button 
                                onclick="copyCodeSnippet(this)" 
                                class="absolute top-3 right-3 z-20 bg-slate-800/90 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-medium py-1.5 px-2.5 rounded border border-slate-700 transition-all flex items-center gap-1.5 shadow-md active:scale-95 cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                                </svg>
                                <span class="btn-text">Copy</span>
                            </button>

                            <pre 
                                class="bg-slate-900 text-slate-100 p-5 rounded-lg font-mono text-xs overflow-auto max-h-80 leading-relaxed text-left" 
                                id="codeBlock"
                            ></pre>
                        </div>
                    </div>
                    
                    <div class="px-6 py-3 border-t border-gray-100 bg-gray-50 flex justify-end">
                        <button onclick="closeSnippetModal()" class="text-sm bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded transition-colors cursor-pointer">
                            Dismiss
                        </button>
                    </div>
                </div>
            </div>

            <!-- Scripts -->
            <script>
            function openEditTokenModal(id, name) {
                const modal = document.getElementById('editTokenModal');
                const form = document.getElementById('editTokenForm');
                
                form.action = `/admin/tokens/${id}`;
                
                document.getElementById('edit_token_name').value = name;
                
                modal.classList.remove('hidden');
            }

            function closeEditTokenModal() {
                document.getElementById('editTokenModal').classList.add('hidden');
            }

            function openSnippetModal(name, token) {
                const modal = document.getElementById('snippetModal');
                const title = document.getElementById('modalTitle');
                const codeBlock = document.getElementById('codeBlock');
                
                const appUrl = "{{ config('app.url') }}";

                title.innerText = `Integration Script for ${name}`;

                codeBlock.innerText = `\n` + 
                                    `<script\n` +
                                    `    id="chatbot-initializer"\n` +
                                    `    src="${appUrl}/build/widget.js"\n` +
                                    `    data-app-url="${appUrl}"\n` +
                                    `    data-client-token="${token}"\n` +
                                    `    data-position-bottom="20px"\n` +
                                    `    data-position-right="20px"\n` +
                                    `    data-button-width="60px"\n` +
                                    `    data-button-height="60px"\n` +
                                    `    charset="UTF-8"\n` +
                                    `<\/script>`;
                    
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closeSnippetModal() {
                const modal = document.getElementById('snippetModal');
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }

            function copyCodeSnippet(buttonElement) {
                const codeBlock = document.getElementById('codeBlock');
                if (!codeBlock) return;

                let textToCopy = codeBlock.innerText || codeBlock.textContent;

                navigator.clipboard.writeText(textToCopy).then(() => {
                    const textSpan = buttonElement.querySelector('.btn-text');
                    
                    textSpan.textContent = 'Copied!';
                    buttonElement.classList.remove('bg-slate-800/90', 'text-slate-300', 'border-slate-700');
                    buttonElement.classList.add('bg-emerald-600', 'text-white', 'border-emerald-500');

                    setTimeout(() => {
                        textSpan.textContent = 'Copy';
                        buttonElement.classList.remove('bg-emerald-600', 'text-white', 'border-emerald-500');
                        buttonElement.classList.add('bg-slate-800/90', 'text-slate-300', 'border-slate-700');
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy the script: ', err);
                });
            }

            function copySessionId(text, element) {
                if (!navigator.clipboard) {
                    const textArea = document.createElement("textarea");
                    textArea.value = text;
                    document.body.appendChild(textArea);
                    textArea.select();
                    try {
                        document.execCommand('copy');
                        showFeedback(element);
                    } catch (err) {
                        console.error('Fallback: Unable to copy', err);
                    }
                    document.body.removeChild(textArea);
                    return;
                }

                navigator.clipboard.writeText(text).then(function() {
                    showFeedback(element);
                }, function(err) {
                    console.error('Async: Could not copy text: ', err);
                });
            }

            function showFeedback(element) {
                const container = element.closest('.flex');
                const textNode = container.querySelector('a');
                const originalTitle = textNode.innerText;

                textNode.innerText = 'Copied!';
                textNode.classList.add('text-green-600', 'font-semibold');
                
                setTimeout(() => {
                    textNode.innerText = originalTitle;
                    textNode.classList.remove('text-green-600', 'font-semibold');
                }, 1000);
            }
            </script>

        </main>
    </div>

</body>
</html>