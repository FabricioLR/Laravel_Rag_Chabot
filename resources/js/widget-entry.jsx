import React from 'react';
import { createRoot } from 'react-dom/client';
import ChatWidget from './components/ChatWidget';
import widgetStyles from '../css/widget.css?inline';

(function () {
    const initScript = document.getElementById('chatbot-initializer');

    if (!initScript) {
        console.error("Chatbot widget initialization failed: Missing '#chatbot-initializer' ID.");
        return;
    }

    const appUrl = initScript.getAttribute('data-app-url');
    const clientToken = initScript.getAttribute('data-client-token');

    if (!appUrl || !clientToken) {
        console.warn("ChatEngine: Aborting initialization. Missing appUrl or clientToken.", { appUrl, clientToken });
        return;
    }

    const widgetTarget = document.createElement('div');
    widgetTarget.id = 'chat-widget-root-host';
    document.body.appendChild(widgetTarget);

    const shadowRoot = widgetTarget.attachShadow({ mode: 'open' });

    const reactRootTarget = document.createElement('div');
    reactRootTarget.id = 'chat-widget-react-root';

    const styleElement = document.createElement('style');
    styleElement.textContent = widgetStyles;

    shadowRoot.appendChild(styleElement);
    shadowRoot.appendChild(reactRootTarget);

    const positionBottom = initScript.getAttribute('data-position-bottom') || '20px';
    const positionRight = initScript.getAttribute('data-position-right') || '20px';
    const buttonWidth = initScript.getAttribute('data-button-width') || '60px';
    const buttonHeight = initScript.getAttribute('data-button-height') || '60px';

    const root = createRoot(reactRootTarget);
    root.render(
        <React.StrictMode>
            <ChatWidget 
                appUrl={appUrl} 
                clientToken={clientToken} 
                positionBottom={positionBottom}
                positionRight={positionRight}
                buttonWidth={buttonWidth}
                buttonHeight={buttonHeight}
            />
        </React.StrictMode>
    );
})();