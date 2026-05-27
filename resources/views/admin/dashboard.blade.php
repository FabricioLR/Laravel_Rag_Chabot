<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WordPress Sync Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans antialiased">

    <div class="min-h-screen flex flex-col">
        <nav class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
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
            <div class="mb-8">
                <h1 class="text-3xl font-extrabold text-gray-900">WordPress Ingestion Metrics</h1>
            </div>

            @if(isset($error))
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded shadow-sm">
                    <p class="text-sm text-red-700 font-semibold">Warning: Core aggregation pipeline encountered errors.</p>
                    <p class="text-xs text-red-600 mt-1">{{ $error }}</p>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <p class="text-sm font-medium text-gray-400 uppercase tracking-wider">Total WordPress Posts</p>
                    <p class="text-4xl font-bold text-gray-900 mt-2">{{ number_format($total_wordpress_posts) }}</p>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <p class="text-sm font-medium text-gray-400 uppercase tracking-wider">Indexed Posts (pgvector)</p>
                    <p class="text-4xl font-bold text-emerald-600 mt-2">{{ number_format($indexed_posts_count) }}</p>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <p class="text-sm font-medium text-gray-400 uppercase tracking-wider">Unindexed Posts Remaining</p>
                    <p class="text-4xl font-bold text-amber-500 mt-2">{{ number_format($posts_remaining) }}</p>
                </div>

            </div>
        </main>
    </div>

</body>
</html>