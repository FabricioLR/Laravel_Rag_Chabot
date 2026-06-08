import React, { useState, useEffect, useRef } from 'react';
import MessageFeedback from './MessageFeedback';

const SESSION_EXPIRATION_MS = 10 * 60 * 1000; 

export default function ChatWidget({ appUrl, clientToken }) {
  const chatApiEndpoint = `${appUrl}/api/chat`;
  const categoriesApiEndpoint = `${appUrl}/api/chat/categories`;
  const initialMessage = "Qual categoria principal você tem interesse?";

  const [isOpen, setIsOpen] = useState(false);
  const [sessionId, setSessionId] = useState(null);
  const [currentStep, setCurrentStep] = useState('start');
  const [activeFilters, setActiveFilters] = useState({ main: null, child: null });
  const [messages, setMessages] = useState([]);
  const [inputValue, setInputValue] = useState('');
  const [inputPlaceholder, setInputPlaceholder] = useState("Selecione uma opção acima...");
  const [isDisabled, setIsDisabled] = useState(true);
  const [isLoading, setIsLoading] = useState(false);
  const [activeOptions, setActiveOptions] = useState([]);

  const messagesEndRef = useRef(null);

  const clearChatSessionData = () => {
    localStorage.removeItem('chat_widget_session');
    localStorage.removeItem('chat_widget_session_timestamp');
    localStorage.removeItem('chat_widget_state');
    localStorage.removeItem('chat_widget_filters');
    localStorage.removeItem('chat_widget_local_history');
  };

  useEffect(() => {
    let currentSessionId = localStorage.getItem('chat_widget_session');
    const sessionTimestamp = localStorage.getItem('chat_widget_session_timestamp');
    const now = Date.now();

    if (currentSessionId && sessionTimestamp) {
      const timeElapsed = now - parseInt(sessionTimestamp, 10);
      
      if (timeElapsed > SESSION_EXPIRATION_MS) {
        clearChatSessionData();
        currentSessionId = null; 
      }
    } else if (currentSessionId && !sessionTimestamp) {
      clearChatSessionData();
      currentSessionId = null;
    }

    if (!currentSessionId) {
      currentSessionId = crypto.randomUUID();
      localStorage.setItem('chat_widget_session', currentSessionId);
      localStorage.setItem('chat_widget_session_timestamp', now.toString());
    } else {
      localStorage.setItem('chat_widget_session_timestamp', now.toString());
    }

    setSessionId(currentSessionId);
    
    setCurrentStep('start');
    setActiveFilters({ main: null, child: null });
    localStorage.setItem('chat_widget_state', 'start');
    localStorage.removeItem('chat_widget_filters');
    localStorage.removeItem('chat_widget_local_history'); 
  }, []);

  useEffect(() => {
    if (sessionId) {
      syncAndInitialize();
    }
  }, [sessionId]);

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [isLoading, activeOptions, isOpen]);

  const updateState = (step, filters = null) => {
    setCurrentStep(step);
    localStorage.setItem('chat_widget_state', step);
    if (filters) {
      setActiveFilters(filters);
      localStorage.setItem('chat_widget_filters', JSON.stringify(filters));
    }
    localStorage.setItem('chat_widget_session_timestamp', Date.now().toString());
  };

  const syncAndInitialize = async () => {
    const historyApiEndpoint = `${appUrl}/api/chat/history/${sessionId}`;
    let historicalMessages = [];

    try {
      const response = await fetch(historyApiEndpoint, {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-Client-Token': clientToken }
      });

      if (response.ok) {
        const data = await response.json();
        historicalMessages = data.messages || [];
      }
    } catch (error) {
      console.error("Failed to recover database history:", error);
    }

    const formattedHistory = historicalMessages.map(msg => ({
      id: msg.id,
      feedback: msg.feedback,
      text: msg.text,
      sender: msg.sender,
      isApi: msg.sender === 'bot'
    }));

    try {
      const res = await fetch(categoriesApiEndpoint, {
        headers: { 'X-Client-Token': clientToken, 'Accept': 'application/json' }
      });
      const data = await res.json();
      
      updateState('awaiting_main');
      
      setMessages([
        ...formattedHistory,
        { text: initialMessage, sender: 'bot', isApi: false }
      ]);
      setActiveOptions([{ name: 'Geral' }, ...data.categories]);

    } catch (err) {
      console.error("Error setting up categories configuration:", err);
      setMessages([...formattedHistory, { text: "Desculpe, tive problemas para carregar as categorias.", sender: 'bot', isApi: false }]);
    }
  };

  const runStateEngine = async (text) => {
    localStorage.setItem('chat_widget_session_timestamp', Date.now().toString());

    if (currentStep === 'awaiting_main') {
      setActiveOptions([]);

      if (text === 'Geral' || text === 'General') {
        updateState('completed', { main: 'Geral', child: 'Geral' });
        setMessages(prev => [...prev, { text: "Você selecionou Geral. O que você gostaria de saber?", sender: 'bot', isApi: false }]);
        setIsDisabled(false);
        setInputPlaceholder("Digite uma mensagem...");
        return;
      }

      const match = text.match(/^([0-9]+)\s*-/);
      const prefix = match ? match[1] : null;

      if (!prefix) {
        setMessages(prev => [...prev, { text: "Por favor, use os botões fornecidos para escolher uma categoria válida.", sender: 'bot', isApi: false }]);
        return;
      }

      try {
        const res = await fetch(`${categoriesApiEndpoint}?parent=${prefix}`, {
          headers: { 'X-Client-Token': clientToken, 'Accept': 'application/json' }
        });
        const data = await res.json();

        updateState('awaiting_child', { main: text, child: null });
        setMessages(prev => [...prev, { text: `Entendido! Selecione uma subcategoria específica em '${text}':`, sender: 'bot', isApi: false }]);
        setActiveOptions([{ name: 'Geral' }, ...data.categories]);
      } catch (err) {
        console.error(err);
        setMessages(prev => [...prev, { text: "Desculpe, tive problemas para carregar as subcategorias.", sender: 'bot', isApi: false }]);
      }
      return;
    }

    if (currentStep === 'awaiting_child') {
      setActiveOptions([]);
      updateState('completed', { main: activeFilters.main, child: text });
      setMessages(prev => [...prev, { text: `Perfeito! Filtros aplicados para focar na sua escolha. Como posso te ajudar hoje?`, sender: 'bot', isApi: false }]);
      setIsDisabled(false);
      setInputPlaceholder("Digite uma mensagem...");
      return;
    }

    if (currentStep === 'completed') {
      setIsLoading(true);
      setIsDisabled(true);
      try {
        const response = await fetch(chatApiEndpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Client-Token': clientToken },
          body: JSON.stringify({
            chatInput: text,
            sessionId: sessionId,
            mainCategory: activeFilters.main,
            childCategory: activeFilters.child
          })
        });

        setIsLoading(false);
        setIsDisabled(false);
        const data = await response.json();
        setMessages(prev => [...prev, { id: data.conversationId, feedback: null, text: data.answer || "Desculpe, não consegui processar.", sender: 'bot', isApi: true }]);
      } catch (error) {
        setIsLoading(false);
        setIsDisabled(false);
        setMessages(prev => [...prev, { text: "Desculpe, estou com problemas para me conectar no momento.", sender: 'bot', isApi: false }]);
      }
    }
  };

  const handleSendMessage = async (overrideText = null) => {
    const text = overrideText || inputValue.trim();
    if (!text) return;

    setMessages(prev => [...prev, { text, sender: 'user', isApi: false }]);
    if (!overrideText) setInputValue('');

    await runStateEngine(text);
  };

  const handleReset = () => {
    localStorage.setItem('chat_widget_session_timestamp', Date.now().toString());
    updateState('start', { main: null, child: null });
    setMessages([]);
    setActiveOptions([]);
    setIsDisabled(true);
    setInputPlaceholder("Selecione uma opção acima...");
    syncAndInitialize();
  };

  const formatMarkdownLinks = (text) => {
    if (!text) return '';
    const markdownLinkRegex = /\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g;
    const parts = [];
    let lastIndex = 0;
    let match;

    while ((match = markdownLinkRegex.exec(text)) !== null) {
      if (match.index > lastIndex) parts.push(text.substring(lastIndex, match.index));
      parts.push(<a key={match.index} href={match[2]} target="_blank" rel="noopener noreferrer" className="text-blue-600 underline">{match[1]}</a>);
      lastIndex = markdownLinkRegex.lastIndex;
    }
    if (lastIndex < text.length) parts.push(text.substring(lastIndex));
    return parts.length > 0 ? parts : text;
  };

  function formatBotResponse(rawText) {
    if (!rawText) return '';

    let formatted = rawText;

    // 1. Decode Unicode sequences (e.g., \u00ea -> ê)
    // Most modern fetch/axios setups decode this automatically, but this handles raw strings safely
    try {
        formatted = JSON.parse(`"${formatted.replace(/"/g, '\\"')}"`);
    } catch (e) {
        // Fallback placeholder if string escaping encounters structural issues
    }

    // 2. Convert standard Markdown links: [Text](URL) -> <a href="URL">Text</a>
    // Cleans up the escaped slashes (\/) typically sent back by PHP/Laravel json_encode
    formatted = formatted.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, function(match, text, url) {
        const cleanUrl = url.replace(/\\/g, ''); // Remove JSON escape backslashes
        return `<a href="${cleanUrl}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:underline font-medium">${text}</a>`;
    });

    // 3. Convert explicit angled bracket links: <URL> -> <a href="URL">URL</a>
    formatted = formatted.replace(/<(https?:\/\/[^>]+)>/g, function(match, url) {
        const cleanUrl = url.replace(/\\/g, '');
        return `<a href="${cleanUrl}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:underline font-medium">${cleanUrl}</a>`;
    });

    // 4. Convert structural newlines (\n) into functional HTML break tags
    formatted = formatted.replace(/\n/g, '<br>');

    return formatted;
  }

  return (
    <div className="fixed bottom-5 right-5 z-[999999] font-sans">
      {isOpen && (
        <div className="flex flex-col w-[350px] h-[450px] bg-white rounded-xl shadow-2xl overflow-hidden mb-4 border border-slate-100">
          <div className="bg-blue-600 text-white p-4 font-bold flex justify-between items-center">
            <span>Assistente Virtual</span>
            <div className="flex items-center gap-3">
              {currentStep === 'completed' && (
                <button onClick={handleReset} className="cursor-pointer bg-white/15 text-white border border-white/30 px-2 py-1 rounded text-xs">
                  Alterar Categoria
                </button>
              )}
              <span onClick={() => setIsOpen(false)} className="cursor-pointer text-lg">✕</span>
            </div>
          </div>

          <div className="flex-1 p-4 overflow-y-auto bg-slate-50 flex flex-col gap-3">
            {messages.map((msg, index) => (
              <div 
                key={index} 
                className={`p-2.5 max-w-[80%] rounded-lg leading-relaxed ${
                  msg.sender === 'user' 
                    ? 'bg-slate-200 text-slate-800 self-end ml-auto' 
                    : 'bg-slate-200 text-slate-800 self-start'
                }`}
              >

                <div className={`max-w-xs md:max-w-md p-3 rounded-lg overflow-hidden break-words whitespace-pre-wrap ${msg.sender === 'bot' ? 'bg-gray-100' : 'bg-gray-100 text-slate-800'}`}>
                  {msg.sender === 'bot' ? (
                      <span dangerouslySetInnerHTML={{ __html: formatBotResponse(msg.text) }} />
                  ) : (
                      msg.text
                  )}
                </div>
                
                {msg.sender === 'bot' && msg.isApi && msg.feedback === null && (
                  <MessageFeedback 
                    appUrl={appUrl}
                    clientToken={clientToken}
                    conversationId={msg.id}
                  />
                )}
              </div>
            ))}

            {isLoading && (
              <div className="bg-slate-200 text-slate-800 self-start p-3 rounded-lg flex gap-1">
                <span className="w-1.5 h-1.5 bg-slate-500 rounded-full animate-bounce"></span>
                <span className="w-1.5 h-1.5 bg-slate-500 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                <span className="w-1.5 h-1.5 bg-slate-500 rounded-full animate-bounce [animation-delay:0.4s]"></span>
              </div>
            )}

            {activeOptions.length > 0 && (
              <div className="flex flex-wrap gap-2 mt-2 max-w-[90%] self-start">
                {activeOptions.map((opt, i) => (
                  <button key={i} onClick={() => handleSendMessage(opt.name)} className="cursor-pointer bg-white text-blue-600 border border-blue-600 px-3 py-1.5 rounded-full font-medium text-xs hover:bg-blue-600 hover:text-white transition-all">
                    {opt.name}
                  </button>
                ))}
              </div>
            )}
            <div ref={messagesEndRef} />
          </div>

          <form onSubmit={(e) => { e.preventDefault(); handleSendMessage(); }} className="flex border-t border-slate-200 p-2.5 bg-white gap-2">
            <input type="text" value={inputValue} onChange={(e) => setInputValue(e.target.value)} disabled={isDisabled} placeholder={inputPlaceholder} className="flex-1 border border-slate-300 p-2 rounded-md outline-none focus:border-blue-500 disabled:bg-slate-100 text-sm" autoComplete="off" />
            <button type="submit" disabled={isDisabled || !inputValue.trim()} className="cursor-pointer bg-blue-600 text-white px-3 py-2 rounded-md font-medium disabled:cursor-not-allowed disabled:bg-slate-300 text-sm">
              Enviar
            </button>
          </form>
        </div>
      )}

      {!isOpen && (
        <div onClick={() => setIsOpen(true)} className="w-[60px] h-[60px] bg-blue-600 rounded-full flex items-center justify-center cursor-pointer shadow-xl hover:scale-105 transition-transform">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
        </div>
      )}
    </div>
  );
}