import React from 'react';
import { createRoot } from 'react-dom/client';
import ChatWidget from './components/ChatWidget';
import '../css/widget.css';

window.addEventListener('chatbot-ready', (event) => {
    const { appUrl, token } = event.detail;

    if (!appUrl || !token) {
        console.error("Chatbot widget initialization failed: Missing required parameters in event payload.");
        return;
    }

    if (document.getElementById('chat-widget-react-root')) {
        return;
    }

    const widgetTarget = document.createElement('div');
    widgetTarget.id = 'chat-widget-react-root';
    document.body.appendChild(widgetTarget);

    const root = createRoot(widgetTarget);
    root.render(
        <React.StrictMode>
            <ChatWidget appUrl={appUrl} clientToken={token} />
        </React.StrictMode>
    );
});