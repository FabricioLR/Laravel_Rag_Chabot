(function () {
    const scriptTag = document.currentScript;
    const widgetToken = scriptTag.getAttribute('data-token');
    const apiBaseUrl = "http://172.23.189.118:8000/api/chat";

    let sessionId = sessionStorage.getItem('chat_widget_session') || null;

    if (!sessionId){
        sessionId = crypto.randomUUID();
        sessionStorage.setItem('chat_widget_session', sessionId);
    }

    // 2. Inject CSS Styles directly into the host page
    const styles = `
        #chat-widget-container { position: fixed; bottom: 20px; right: 20px; z-index: 999999; font-family: Arial, sans-serif; }
        #chat-widget-button { width: 60px; height: 60px; background: #2563eb; rounded-circle: 50%; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.15); transition: transform 0.2s; }
        #chat-widget-button:hover { transform: scale(1.05); }
        #chat-widget-window { display: none; width: 350px; height: 450px; background: #fff; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.2); flex-direction: column; overflow: hidden; margin-bottom: 15px; }
        #chat-widget-header { background: #2563eb; color: #fff; padding: 15px; font-weight: bold; display: flex; justify-content: space-between; align-items: center; }
        #chat-widget-messages { flex: 1; padding: 15px; overflow-y: auto; background: #f8fafc; }
        .chat-msg { margin-bottom: 10px; max-width: 80%; padding: 8px 12px; border-radius: 8px; font-size: 14px; line-height: 1.4; }
        .chat-msg.bot { background: #e2e8f0; color: #1e293b; align-self: flex-start; }
        .chat-msg.user { background: #2563eb; color: #fff; align-self: flex-end; margin-left: auto; }
        #chat-widget-input-area { display: flex; border-top: 1px solid #e2e8f0; padding: 10px; background: #fff; }
        #chat-widget-input { flex: 1; border: 1px solid #cbd5e1; padding: 8px; border-radius: 6px; outline: none; }
        #chat-widget-send { background: #2563eb; color: white; border: none; padding: 8px 12px; margin-left: 8px; border-radius: 6px; cursor: pointer; }
    `;

    const styleSheet = document.createElement("style");
    styleSheet.innerText = styles;
    document.head.appendChild(styleSheet);

    // 3. Create Widget HTML Structure
    const widgetContainer = document.createElement('div');
    widgetContainer.id = 'chat-widget-container';
    widgetContainer.innerHTML = `
        <div id="chat-widget-window">
            <div id="chat-widget-header">
                <span>AI Assistant</span>
                <span id="chat-widget-close" style="cursor:pointer;">✕</span>
            </div>
            <div id="chat-widget-messages" style="display: flex; flex-direction: column;">
                <div class="chat-msg bot">Hello! How can I help you today?</div>
            </div>
            <div id="chat-widget-input-area">
                <input type="text" id="chat-widget-input" placeholder="Type a message..." autocomplete="off" />
                <button id="chat-widget-send">Send</button>
            </div>
        </div>
        <div id="chat-widget-button">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
        </div>
    `;
    document.body.appendChild(widgetContainer);

    // 4. UI Elements & Event Listeners
    // 4. UI Elements & Event Listeners
    const widgetButton = document.getElementById('chat-widget-button');
    const widgetWindow = document.getElementById('chat-widget-window');
    const widgetClose = document.getElementById('chat-widget-close');
    const sendButton = document.getElementById('chat-widget-send');
    const inputField = document.getElementById('chat-widget-input');
    const messagesContainer = document.getElementById('chat-widget-messages');

    // When clicking the main floating chat icon button
    widgetButton.onclick = () => {
        widgetWindow.style.display = 'flex';   // Open the chat window
        widgetButton.style.display = 'none';   // Hide the floating chat icon
        inputField.focus();
    };

    // When clicking the "✕" inside the chat header
    widgetClose.onclick = () => {
        widgetWindow.style.display = 'none';   // Hide the chat window
        widgetButton.style.display = 'flex';   // Show the floating chat icon back again
    };

    // 5. Send Message Logic
    async function sendMessage() {
        const text = inputField.value.trim();
        if (!text) return;

        appendMessage(text, 'user');
        inputField.value = '';

        try {
            const response = await fetch(apiBaseUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    chatInput: text,
                    //widget_token: widgetToken,
                    sessionId: sessionId
                })
            });
            const data = await response.json();
        
            appendMessage(data.answer, 'bot');
        } catch (error) {
            console.error("Chat error:", error);
            appendMessage("Sorry, I'm having trouble connecting right now.", 'bot');
        }
    }

    function appendMessage(text, sender) {
        const msgDiv = document.createElement('div');
        msgDiv.classList.add('chat-msg', sender);
        msgDiv.innerText = text;
        messagesContainer.appendChild(msgDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    sendButton.onclick = sendMessage;
    inputField.onkeypress = (e) => { if (e.key === 'Enter') sendMessage(); };
})();