<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LLM Generation Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        #telemetry-container:fullscreen {
            width: 100vw !important;
            height: 100vh !important;
            background-color: #111827 !important;
            max-width: none !important;
        }

        #telemetry-container:fullscreen #telemetry-box {
            height: 100vh !important;
            max-height: none !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 2.5rem !important;
            overflow-y: auto !important;
        }

        #telemetry-container:fullscreen #telemetry-box .flex-1 {
            font-size: 0.875rem !important;
            line-height: 1.625 !important;
        }
    </style>
</head>
<body class="bg-gray-50 py-12 px-6">
    <div class="max-w-5xl mx-auto bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-8 py-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold text-gray-900">LLM Generation Details</h1>
                <p class="text-xs text-gray-500 font-mono mt-1">Session ID: {{ $conversation->session_id }}</p>
            </div>
            <a href="{{ url()->previous() }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">&larr; Back</a>
        </div>

        @if($conversation->telemetry)
        <div class="p-8 space-y-8">
            {{-- LLM Metrics Overview --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                    <span class="text-xs text-gray-400 font-semibold uppercase block">Model Name</span>
                    <span class="text-gray-900 font-mono font-medium text-sm">{{ $conversation->telemetry->model }}</span>
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

            {{-- Query Information Section --}}
            <div class="space-y-3">
                <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider">User Query Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                        <span class="text-xs text-gray-400 font-semibold uppercase block mb-1">Original User Input</span>
                        <p class="text-sm text-gray-800 font-medium whitespace-pre-wrap">{{ $conversation->telemetry->user_input ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-gray-400 font-semibold uppercase block">Rewritten Query</span>
                            @if($conversation->telemetry->rewritten_query)
                                <span class="px-2 py-0.5 text-[10px] font-semibold text-emerald-700 bg-emerald-100 rounded-full">Optimized</span>
                            @else
                                <span class="px-2 py-0.5 text-[10px] font-semibold text-gray-500 bg-gray-200 rounded-full">Unchanged</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-800 font-medium whitespace-pre-wrap">{{ $conversation->telemetry->rewritten_query ?? $conversation->telemetry->user_input ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            {{-- Pipeline Performance Breakdown --}}
            <div class="space-y-3">
                <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Pipeline Execution Latencies</h2>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500 block text-xs uppercase font-semibold">Query Rewriter LLM:</span>
                        <span class="font-mono text-gray-800 font-medium">{{ $conversation->telemetry->rewrite_duration_ms ?? '0' }} ms</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-xs uppercase font-semibold">Embedding Generation:</span>
                        <span class="font-mono text-gray-800 font-medium">{{ $conversation->telemetry->embedding_duration_ms ?? '0' }} ms</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-xs uppercase font-semibold">Vector Context Search:</span>
                        <span class="font-mono text-gray-800 font-medium">{{ $conversation->telemetry->database_duration_ms ?? '0' }} ms</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-xs uppercase font-semibold">Main LLM Generation:</span>
                        <span class="font-mono text-gray-800 font-medium">{{ $conversation->telemetry->llm_duration_ms }} ms</span>
                    </div>
                </div>
            </div>

            {{-- Token Metrics Breakdown (Separated) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                {{-- Query Rewriter LLM Metrics --}}
                <div class="space-y-3">
                    <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Query Rewriter Tokens</h2>
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 space-y-2.5 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Prompt / Input Tokens:</span>
                            <span class="font-mono text-gray-700">{{ $conversation->telemetry->rewrite_prompt_tokens ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Completion / Output Tokens:</span>
                            <span class="font-mono text-gray-700">{{ $conversation->telemetry->rewrite_completion_tokens ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between font-bold border-t border-dashed border-gray-200 pt-2 text-slate-700">
                            <span>Rewriter Total:</span>
                            <span>{{ $conversation->telemetry->rewrite_total_tokens ?? 0 }} tokens</span>
                        </div>
                    </div>
                </div>

                {{-- Main LLM Metrics --}}
                <div class="space-y-3">
                    <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Main LLM Tokens</h2>
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 space-y-2.5 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Prompt / Input Tokens:</span>
                            <span class="font-mono text-gray-700">{{ $conversation->telemetry->prompt_tokens ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Completion / Output Tokens:</span>
                            <span class="font-mono text-gray-700">{{ $conversation->telemetry->completion_tokens ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between font-bold border-t border-dashed border-gray-200 pt-2 text-indigo-700">
                            <span>Main Answer Total:</span>
                            <span>{{ $conversation->telemetry->total_tokens ?? 0 }} tokens</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Categories --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-slate-50/50 p-4 rounded-lg border border-slate-100 flex justify-between items-center">
                    <div>
                        <span class="text-xs text-gray-400 font-semibold uppercase block">Main Category</span>
                        <span class="text-gray-900 font-semibold mt-0.5 block">{{ $conversation->telemetry->main_category ?? 'N/A' }}</span>
                    </div>
                </div>
                <div class="bg-slate-50/50 p-4 rounded-lg border border-slate-100 flex justify-between items-center">
                    <div>
                        <span class="text-xs text-gray-400 font-semibold uppercase block">Child Category</span>
                        <span class="text-gray-900 font-medium mt-0.5 block text-sm">{{ $conversation->telemetry->child_category ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
            
            <hr class="border-gray-100">

            {{-- Compiled Prompt Execution --}}
            <div class="space-y-6">
                <div>
                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider mb-2">Compiled Agent Prompt Execution</h3>
                    <div id="telemetry-container" class="relative group">
                        <button 
                            onclick="toggleTelemetryFullscreen()" 
                            class="absolute top-4 right-4 z-50 p-2 bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-white rounded border border-gray-700 transition opacity-0 group-hover:opacity-100 focus:opacity-100"
                            title="Toggle Full Screen"
                        >
                            <svg id="fullscreen-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M20.25 3.75v4.5m0-4.5h-4.5m4.5 0L15 9m-11.25 11.25v-4.5m0 4.5h4.5m-4.5 0L9 15m11.25 5.25v-4.5m0 4.5h-4.5m4.5 0L15 15" />
                            </svg>
                        </button>

                        <div id="telemetry-box" class="flex flex-col bg-gray-900 text-gray-100 text-xs font-mono p-4 rounded-lg border border-gray-800 whitespace-pre-wrap max-h-96 overflow-y-auto [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-track]:bg-gray-900 [&::-webkit-scrollbar-thumb]:bg-gray-700 [&::-webkit-scrollbar-thumb]:rounded-full">
                            <div class="flex-1">
                                {{ $conversation->telemetry->compiled_prompt }}
                                {{ $conversation->answer }}
                            </div>
                        </div>
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

    <script>
        function toggleTelemetryFullscreen() {
            const container = document.getElementById('telemetry-container');
            const icon = document.getElementById('fullscreen-icon');

            if (!document.fullscreenElement) {
                container.requestFullscreen().catch(err => {
                    alert(`Error entering full-screen: ${err.message}`);
                });
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9L3 3m12 6V4.5M15 9h4.5M15 9l6-6M9 15v4.5M9 15H4.5M9 15l-6 6m12-5v4.5M15 15h4.5M15 15l6 6" />';
            } else {
                document.exitFullscreen();
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M20.25 3.75v4.5m0-4.5h-4.5m4.5 0L15 9m-11.25 11.25v-4.5m0 4.5h4.5m-4.5 0L9 15m11.25 5.25v-4.5m0 4.5h-4.5m4.5 0L15 15" />';
            }
        }

        document.addEventListener('fullscreenchange', () => {
            if (!document.fullscreenElement) {
                document.getElementById('fullscreen-icon').innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M20.25 3.75v4.5m0-4.5h-4.5m4.5 0L15 9m-11.25 11.25v-4.5m0 4.5h4.5m-4.5 0L9 15m11.25 5.25v-4.5m0 4.5h-4.5m4.5 0L15 15" />';
            }
        });
    </script>
</body>
</html>