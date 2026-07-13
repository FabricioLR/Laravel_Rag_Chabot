<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LLM Generation Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 py-12 px-6">
    <div class="max-w-5xl mx-auto bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <!-- Header -->
        <div class="px-8 py-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold text-gray-900">LLM Generation Telemetry</h1>
                <p class="text-xs text-gray-500 font-mono mt-1">Session ID: {{ $conversation->session_id }}</p>
            </div>
            <a href="{{ url()->previous() }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">&larr; Back</a>
        </div>

        @if($conversation->telemetry)
        <div class="p-8 space-y-8">
            <!-- Parameters Overview -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                    <span class="text-xs text-gray-400 font-semibold uppercase block">Model Name</span>
                    <span class="text-gray-900 font-mono font-medium">{{ $conversation->telemetry->model }}</span>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                    <span class="text-xs text-gray-400 font-semibold uppercase block">Temperature</span>
                    <span class="text-gray-900 font-mono font-medium">{{ $conversation->telemetry->temperature }}</span>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                    <span class="text-xs text-gray-400 font-semibold uppercase block">Max Output Tokens</span>
                    <span class="text-gray-900 font-mono font-medium">{{ $conversation->telemetry->max_tokens }}</span>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                    <span class="text-xs text-gray-400 font-semibold uppercase block">Total Duration</span>
                    <span class="text-indigo-600 font-bold font-mono">{{ $conversation->telemetry->total_duration_ms }} ms</span>
                </div>
            </div>

            <!-- Latencies & Tokens -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Duration Metrics -->
                <div class="space-y-3">
                    <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Pipeline Performance</h2>
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 space-y-2.5 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Embedding Generation:</span>
                            <span class="font-mono text-gray-700">{{ $conversation->telemetry->embedding_duration_ms ?? '0' }} ms</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Vector Search context:</span>
                            <span class="font-mono text-gray-700">{{ $conversation->telemetry->database_duration_ms ?? '0' }} ms</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">LLM Generation latency:</span>
                            <span class="font-mono text-gray-700">{{ $conversation->telemetry->llm_duration_ms }} ms</span>
                        </div>
                    </div>
                </div>

                <!-- Token Breakdown -->
                <div class="space-y-3">
                    <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Token Metrics</h2>
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 space-y-2.5 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Prompt / Input Tokens:</span>
                            <span class="font-mono text-gray-700">{{ $conversation->telemetry->prompt_tokens }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Completion / Output Tokens:</span>
                            <span class="font-mono text-gray-700">{{ $conversation->telemetry->completion_tokens }}</span>
                        </div>
                        <div class="flex justify-between font-bold border-t border-dashed border-gray-200 pt-2 text-indigo-700">
                            <span>Total Usage:</span>
                            <span>{{ $conversation->telemetry->total_tokens }} tokens</span>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="border-gray-100">

            <!-- Deep Prompt inspection -->
            <div class="space-y-6">
                <div>
                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider mb-2">System Prompt</h3>
                    <div class="bg-gray-900 text-gray-100 text-xs font-mono p-4 rounded-lg border border-gray-800 whitespace-pre-wrap max-h-48 overflow-y-auto">
                        {{ $conversation->telemetry->system_prompt }}
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider mb-2">User Prompt</h3>
                    <div class="bg-gray-900 text-gray-100 text-xs font-mono p-4 rounded-lg border border-gray-800 whitespace-pre-wrap max-h-96 overflow-y-auto">
                        {{ $conversation->telemetry->compiled_prompt }}
                        {{ $conversation->answer }}
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="p-12 text-center text-sm text-gray-400 bg-gray-50">
            No pipeline execution logs found associated with this interaction.
        </div>
        @endif
    </div>
</body>
</html>