import React from 'react';
import { createRoot } from 'react-dom/client';
import ChatWidget from './components/ChatWidget';
import '../css/widget.css';

(function () {
    const initScript = document.getElementById('chatbot-initializer');

    if (!initScript) {
        console.error("Chatbot widget initialization failed: Missing '#chatbot-initializer' ID.");
        return;
    }

    const appUrl = initScript.getAttribute('data-app-url');
    const clientToken = initScript.getAttribute('data-client-token');

    const widgetTarget = document.createElement('div');
    widgetTarget.id = 'chat-widget-react-root';
    document.body.appendChild(widgetTarget);

    const root = createRoot(widgetTarget);
    root.render(
        <React.StrictMode>
            <ChatWidget appUrl={appUrl} clientToken={clientToken} />
        </React.StrictMode>
    );
})();