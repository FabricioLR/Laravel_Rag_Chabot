(async function () {
    const initScript = document.getElementById('chatbot-initializer');

    if (!initScript) {
        console.error("Chatbot widget initialization failed: Missing '#chatbot-initializer' ID on script tag.");
        return;
    }

    const laravelAppUrl = initScript.getAttribute('data-app-url');
    const widgetToken = initScript.getAttribute('data-client-token'); 
    const chatApiEndpoint = `${laravelAppUrl}/api/chat`;

    const initialMessage = "Qual categoria principal você tem interesse?";

    console.log("Widget initialized successfully. Target Endpoint:", chatApiEndpoint);

    let sessionId = localStorage.getItem('chat_widget_session') || null;
    let lastActive = localStorage.getItem('chat_widget_session_time') || null;
    const now = Date.now();
    const SESSION_EXPIRATION_MS = 10 * 60 * 1000; 

    if (sessionId && lastActive && (now - lastActive > SESSION_EXPIRATION_MS)){
        sessionId = crypto.randomUUID();
        localStorage.setItem('chat_widget_session', sessionId);
        localStorage.removeItem('chat_widget_state'); 
        localStorage.removeItem('chat_widget_filters');
        localStorage.removeItem('chat_widget_local_history');
    }

    if (!sessionId){
        sessionId = crypto.randomUUID();
        localStorage.setItem('chat_widget_session', sessionId);
        localStorage.removeItem('chat_widget_state'); 
        localStorage.removeItem('chat_widget_filters');
        localStorage.removeItem('chat_widget_local_history');
    }
    localStorage.setItem('chat_widget_session_time', now);

    const localMessages_ = JSON.parse(localStorage.getItem('chat_widget_local_history')) || [];
    if (localMessages_.length == 1){
        if (localMessages_[0].text == initialMessage) localStorage.removeItem('chat_widget_local_history')
    }

    let currentStep = localStorage.getItem('chat_widget_state') || 'start';
    let activeFilters = JSON.parse(localStorage.getItem('chat_widget_filters')) || { main: null, child: null };

    const styles = `
        #chat-widget-container { position: fixed; bottom: 20px; right: 20px; z-index: 999999; font-family: Arial, sans-serif; }
        #chat-widget-button { width: 60px; height: 60px; background: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.15); transition: transform 0.2s; }
        #chat-widget-button:hover { transform: scale(1.05); }
        #chat-widget-window { display: none; width: 350px; height: 450px; background: #fff; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.2); flex-direction: column; overflow: hidden; margin-bottom: 15px; }
        #chat-widget-header { background: #2563eb; color: #fff; padding: 15px; font-weight: bold; display: flex; justify-content: space-between; align-items: center; }
        .chat-reset-btn { background: rgba(255, 255, 255, 0.15); color: #fff; border: 1px solid rgba(255, 255, 255, 0.3); padding: 4px 8px; border-radius: 4px; font-size: 11px; cursor: pointer; transition: background 0.2s; margin-left: auto; margin-right: 12px; font-weight: normal; }
        .chat-reset-btn:hover { background: rgba(255, 255, 255, 0.25); }
        #chat-widget-messages { flex: 1; padding: 15px; overflow-y: auto; background: #f8fafc; }
        .chat-msg { margin-bottom: 10px; max-width: 80%; padding: 8px 12px; border-radius: 8px; font-size: 14px; line-height: 1.4; }
        .chat-msg.bot { background: #e2e8f0; color: #1e293b; align-self: flex-start; }
        .chat-msg.user { background: #2563eb; color: #fff; align-self: flex-end; margin-left: auto; }
        #chat-widget-input-area { display: flex; border-top: 1px solid #e2e8f0; padding: 10px; background: #fff; }
        #chat-widget-input { flex: 1; border: 1px solid #cbd5e1; padding: 8px; border-radius: 6px; outline: none; }
        #chat-widget-send { background: #2563eb; color: white; border: none; padding: 8px 12px; margin-left: 8px; border-radius: 6px; cursor: pointer; }
        
        .chat-options-container { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; margin-bottom: 10px; align-self: flex-start; max-width: 90%; }
        .chat-option-btn { background: #fff; color: #2563eb; border: 1px solid #2563eb; padding: 6px 12px; border-radius: 16px; font-size: 13px; cursor: pointer; font-weight: 500; transition: all 0.2s; }
        .chat-option-btn:hover { background: #2563eb; color: #fff; }
        .chat-msg.loading { background: #e2e8f0; align-self: flex-start; padding: 12px 16px;}
        .dot {width: 6px; height: 6px; background: #64748b; border-radius: 50%;display: inline-block; animation: wave 1.3s infinite ease-in-out;}
        .dot:nth-child(2) { animation-delay: -1.1s; }
        .dot:nth-child(3) { animation-delay: -0.9s; }
        @keyframes wave {0%, 60%, 100% { transform: translateY(0); }30% { transform: translateY(-6px); }}
    `;

    const styleSheet = document.createElement("style");
    styleSheet.innerText = styles;
    document.head.appendChild(styleSheet);

    const widgetContainer = document.createElement('div');
    widgetContainer.id = 'chat-widget-container';
    
    widgetContainer.innerHTML = `
        <div id="chat-widget-window">
            <div id="chat-widget-header">
                <span>Assistente Virtual</span>
                <button type="button" id="chat-widget-reset" class="chat-reset-btn" style="display: none;">🔄 Alterar Categoria</button>
                <span id="chat-widget-close" style="cursor:pointer;">✕</span>
            </div>
            <div id="chat-widget-messages" style="display: flex; flex-direction: column;">
            </div>
            <div id="chat-widget-input-area">
                <input type="text" id="chat-widget-input" placeholder="Digite uma mensagem..." autocomplete="off" />
                <button type="button" id="chat-widget-send">Enviar</button> 
            </div>
        </div>
        <div id="chat-widget-button">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
        </div>
    `;
    document.body.appendChild(widgetContainer);

    const widgetButton = document.getElementById('chat-widget-button');
    const widgetWindow = document.getElementById('chat-widget-window');
    const widgetClose = document.getElementById('chat-widget-close');
    const sendButton = document.getElementById('chat-widget-send');
    const inputField = document.getElementById('chat-widget-input');
    const messagesContainer = document.getElementById('chat-widget-messages');
    const resetButton = document.getElementById('chat-widget-reset');

    if (currentStep === 'completed') {
        resetButton.style.display = 'inline-block';
    } else {
        resetButton.style.display = 'none';
    }

    widgetButton.onclick = () => {
        widgetWindow.style.display = 'flex';
        widgetButton.style.display = 'none';
        scrollToBottom();
        inputField.focus();
    };

    widgetClose.onclick = () => {
        widgetWindow.style.display = 'none';
        widgetButton.style.display = 'flex';
    };

    resetButton.onclick = async () => {
        updateState('start', { main: null, child: null });
        localStorage.removeItem('chat_widget_local_history');
        
        const activeOptions = messagesContainer.querySelectorAll('.chat-options-container');
        activeOptions.forEach(el => el.remove());

        inputField.disabled = true;
        sendButton.disabled = true;
        
        inputField.placeholder = "Por favor, selecione uma opção acima...";

        await runStateEngine("START_FLOW");
    };

    function saveOnboardingToLocalHistory(text, sender) {
        let localHistory = JSON.parse(localStorage.getItem('chat_widget_local_history')) || [];
        
        localHistory.push({
            text: text,
            sender: sender,
            timestamp: Date.now()
        });
        
        localStorage.setItem('chat_widget_local_history', JSON.stringify(localHistory));
    }

    function updateState(step, filters = null) {
        currentStep = step;
        localStorage.setItem('chat_widget_state', step);
        if (filters) {
            activeFilters = filters;
            localStorage.setItem('chat_widget_filters', JSON.stringify(filters));
        }

        const resetBtn = document.getElementById('chat-widget-reset');
        if (resetBtn) {
            if (currentStep === 'completed') {
                resetBtn.style.display = 'inline-block';
            } else {
                resetBtn.style.display = 'none';
            }
        }
    }

    async function sendMessage(overrideText = null) {
        const text = overrideText || inputField.value.trim();
        if (!text) return;

        appendMessage(text, 'user');
        if (!overrideText) inputField.value = '';

        inputField.disabled = true;

        await runStateEngine(text);
        
        inputField.disabled = false;
        inputField.focus();
        localStorage.setItem('chat_widget_session_time', Date.now());
    }

    async function runStateEngine(text) {
        const inputField = document.getElementById('chat-widget-input');
        const sendButton = document.getElementById('chat-widget-send');

        inputField.disabled = false;
        sendButton.disabled = false;
        
        inputField.placeholder = "Digite uma mensagem...";

        if (currentStep !== 'completed') {
            inputField.disabled = true;
            sendButton.disabled = true;
            
            inputField.placeholder = "Por favor, selecione uma opção acima...";
        }

        if (text === 'START_FLOW'){
            if (currentStep == "completed") return
            try {
                const res = await fetch(`${laravelAppUrl}/api/chat/categories`, {
                    headers: { 'X-Client-Token': widgetToken, 'Accept': 'application/json' }
                });
                const data = await res.json();
                
                updateState('awaiting_main');
                
                const botMsg = initialMessage;
                appendMessage(botMsg, 'bot', [{name: 'Geral'}, ...data.categories]);
                
                saveOnboardingToLocalHistory(botMsg, 'bot');
            } catch (err) {
                console.error(err);
                appendMessage("Desculpe, tive problemas para carregar as categorias.", 'bot');
            }
            return;
        }

        if (currentStep === 'start') {
            try {
                const res = await fetch(`${laravelAppUrl}/api/chat/categories`, {
                    headers: { 'X-Client-Token': widgetToken, 'Accept': 'application/json' }
                });
                const data = await res.json();
                
                updateState('awaiting_main');
                
                const botMsg = initialMessage;
                appendMessage(botMsg, 'bot', [{name: 'Geral'}, ...data.categories]);
                
                saveOnboardingToLocalHistory(botMsg, 'bot');
            } catch (err) {
                console.error(err);
                appendMessage("Desculpe, tive problemas para carregar as categorias.", 'bot');
            }
            return;
        }

        if (currentStep === 'awaiting_main') {
            saveOnboardingToLocalHistory(text, 'user');

            if (text === 'Geral' || text === 'General') {
                updateState('completed', { main: 'Geral', child: 'Geral' });
                const botMsg = "Você selecionou Geral. O que você gostaria de saber?";
                appendMessage(botMsg, 'bot');
                
                saveOnboardingToLocalHistory(botMsg, 'bot');
                inputField.disabled = false;
                sendButton.disabled = false;
                inputField.placeholder = "Digite uma mensagem...";
                return;
            }

            const match = text.match(/^([0-9]+)\s*-/);
            const prefix = match ? match[1] : null;

            if (!prefix) {
                appendMessage("Por favor, use os botões fornecidos para escolher uma categoria válida.", 'bot');
                return;
            }

            try {
                const res = await fetch(`${laravelAppUrl}/api/chat/categories?parent=${prefix}`, {
                    headers: { 'X-Client-Token': widgetToken, 'Accept': 'application/json' }
                });
                const data = await res.json();

                updateState('awaiting_child', { main: text, child: null });

                const botMsg = `Entendido! Selecione uma subcategoria específica em '${text}':`;
                appendMessage(botMsg, 'bot', [{name: 'Geral'}, ...data.categories]);
                
                saveOnboardingToLocalHistory(botMsg, 'bot');
            } catch (err) {
                console.error(err);
                appendMessage("Desculpe, tive problemas para carregar as subcategories.", 'bot');
            }
            return;
        }

        if (currentStep === 'awaiting_child') {
            saveOnboardingToLocalHistory(text, 'user');

            updateState('completed', { main: activeFilters.main, child: text });
            
            inputField.disabled = false;
            sendButton.disabled = false;
            inputField.placeholder = "Digite uma mensagem...";

            const botMsg = `Perfeito! Filtros aplicados para focar na sua escolha. Como posso te ajudar hoje?`;
            appendMessage(botMsg, 'bot');
            
            saveOnboardingToLocalHistory(botMsg, 'bot');

            inputField.disabled = false;
            sendButton.disabled = false;
            inputField.placeholder = "Digite uma mensagem...";

            return;
        }

        if (currentStep === 'completed') {
            const loadingDiv = document.createElement('div');
            loadingDiv.classList.add('chat-msg', 'loading');
            loadingDiv.innerHTML = '<span class="dot"></span><span class="dot"></span><span class="dot"></span>';
            messagesContainer.appendChild(loadingDiv);
            scrollToBottom();

            try {
                const response = await fetch(chatApiEndpoint, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'Accept': 'application/json',
                        'X-Client-Token': widgetToken
                    },
                    body: JSON.stringify({
                        chatInput: text,
                        sessionId: sessionId,
                        mainCategory: activeFilters.main,
                        childCategory: activeFilters.child
                    })
                });

                loadingDiv.remove();

                const data = await response.json();

                if (response.status === 403 || response.status === 401) {
                    appendMessage("Erro de Segurança: Esta conexão não está autorizada.", 'bot');
                    return;
                }

                appendMessage(data.answer || "Desculpe, não consegui processar.", 'bot');
            } catch (error) {
                loadingDiv.remove();

                console.error(error);
                appendMessage("Desculpe, estou com problemas para me conectar no momento.", 'bot');
            }
        }
    }

    function appendMessage(text, sender, options = []) {
        const msgDiv = document.createElement('div');
        msgDiv.classList.add('chat-msg', sender);
        msgDiv.innerHTML = sender === 'bot' ? formatMarkdownLinks(text) : text;
        messagesContainer.appendChild(msgDiv);

        if (options && options.length > 0) {
            const optionsContainer = document.createElement('div');
            optionsContainer.classList.add('chat-options-container');

            options.forEach(option => {
                const button = document.createElement('button');
                button.classList.add('chat-option-btn');
                button.innerText = option.name;
                button.onclick = () => {
                    optionsContainer.remove();
                    sendMessage(option.name);
                };
                optionsContainer.appendChild(button);
            });
            messagesContainer.appendChild(optionsContainer);
        }
        scrollToBottom();
    }

    async function loadHistory() {
        const historyApiEndpoint = `${laravelAppUrl}/api/chat/history/${sessionId}`;
        try {
            const response = await fetch(historyApiEndpoint, {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-Client-Token': widgetToken }
            });

            let dbMessages = [];
            if (response.ok) {
                const data = await response.json();
                dbMessages = data.messages || [];
            }
            
            const localMessages = JSON.parse(localStorage.getItem('chat_widget_local_history')) || [];
            
            let totalMessages = [...localMessages, ...dbMessages];

            if (totalMessages.length > 0) {
                messagesContainer.innerHTML = '';
                
                totalMessages.forEach(msg => appendMessage(msg.text, msg.sender));
                scrollToBottom();
                
                if (currentStep !== 'completed') {
                    inputField.disabled = true;
                    sendButton.disabled = true;
                    inputField.placeholder = "Por favor, selecione uma opção acima...";

                    runStateEngine("START_FLOW");
                }
            } else {
                runStateEngine("START_FLOW");
            }
        } catch (error) {
            console.error(error);
        }
    }

    function scrollToBottom() {
        setTimeout(() => { messagesContainer.scrollTo({ top: messagesContainer.scrollHeight, behavior: 'smooth' }); }, 50);
    }

    function formatMarkdownLinks(text) {
        if (!text) return '';
        const markdownLinkRegex = /\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g;
        return text.replace(markdownLinkRegex, (match, linkText, url) => 
            `<a href="${url}" target="_blank" rel="noopener noreferrer" style="color: #2563eb; text-decoration: underline; font-weight: 500;">${linkText}</a>`
        );
    }

    sendButton.addEventListener('click', (e) => { e.preventDefault(); sendMessage(); });
    inputField.addEventListener('keypress', (e) => { if (e.key === 'Enter') { e.preventDefault(); sendMessage(); } });

    loadHistory();
})();