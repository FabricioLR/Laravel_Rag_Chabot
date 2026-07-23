<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Test Chatbot</title>

    <script
        id="chatbot-initializer"
        data-app-url="{{ env('APP_URL') }}"
        data-client-token="{{ env('LOCAL_WIDGET_TOKEN') }}">
    </script>
    
    @if(class_exists(\Illuminate\Support\Facades\Vite::class) && \Illuminate\Support\Facades\Vite::isRunningHot())
        @viteReactRefresh
        @vite(['resources/css/widget.css', 'resources/js/widget-entry.jsx'])
    @else
        <script src="{{ asset('build/widget.js') }}" defer></script>
    @endif
</head>
<body>
    <p>O widget deve carregar no canto inferior direito.</p>
</body>
</html>