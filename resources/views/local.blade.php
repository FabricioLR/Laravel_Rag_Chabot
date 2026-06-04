<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Teste do Chatbot Local</title>

    <script
        id="chatbot-initializer"
        data-app-url="{{ env('APP_URL') }}"
        data-client-token="{{ env('LOCAL_WIDGET_TOKEN') }}">
    </script>
    
    @viteReactRefresh
    @vite(['resources/css/widget.css', 'resources/js/widget-entry.jsx'])
</head>
<body>
    <h1>Minha Aplicação Laravel</h1>
    <p>O widget deve carregar no canto inferior direito.</p>
</body>
</html>