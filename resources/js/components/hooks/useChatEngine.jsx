import { useState, useEffect, useRef } from 'react';

const minutes = Number(import.meta.env.VITE_SESSION_EXPIRATION_MINUTES) || 45;
const SESSION_EXPIRATION_MS = minutes * 60 * 1000;

const CHAT_STRINGS = {
  INITIAL_MESSAGE: "Olá! Seja bem-vindo ao Transnet IA. 🤖<br>Como posso te ajudar hoje?",
  TOGGLE_BUTTON_LABEL: "Filtrar por módulo (opcional)",
  GERAL_SELECTED: "Você selecionou a categoria Geral. O que você gostaria de saber?",
  INVALID_CATEGORY_ERROR: "Por favor, use os botões fornecidos para escolher uma categoria válida.",
  FILTERS_APPLIED: "Perfeito! Filtros aplicados para focar na sua escolha. Como posso te ajudar hoje?",
  PROCESS_ERROR: "Desculpe, não consegui processar.",
  CONNECTION_ERROR: "Desculpe, estou com problemas para me conectar no momento.",
  INPUT_PLACEHOLDER_ACTIVE: "Digite uma mensagem...",
  INPUT_PLACEHOLDER_DISABLED: "Selecione uma opção acima...",
  DEFAULT_CATEGORY_NOTICE: "Como você enviou uma pergunta direta, prosseguiremos utilizando a categoria **Geral**.",
  
  SUBCATEGORY_PROMPT: (categoryName) => `Combinado! O que você precisa resolver em **${categoryName}**? Selecione uma das opções:`
};

const formatCategoryOption = (cat) => {
  const rawValue = typeof cat === 'string' ? cat : (cat.value || cat.name || '');
  
  let cleanName = rawValue;
  if (cleanName !== 'Geral' && cleanName !== 'Tentar novamente') {
    cleanName = rawValue.replace(/^[\d\s-]+/, '');
    cleanName = cleanName.charAt(0).toUpperCase() + cleanName.slice(1);
    
    return { name: cleanName, value: rawValue, type: "option" };
  }
  
  return { name: cleanName, value: rawValue };
};

export function useChatEngine(appUrl, clientToken) {
  const [isOpen, setIsOpen] = useState(false);
  const [sessionId, setSessionId] = useState(null);
  const [currentStep, setCurrentStep] = useState('start');
  const [activeFilters, setActiveFilters] = useState({ main: null, child: null });
  const [messages, setMessages] = useState([]);
  const [inputValue, setInputValue] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [activeOptions, setActiveOptions] = useState([]);
  
  const lastUserMessageRef = useRef('');

  const isInputDisabled = isLoading;
  const inputPlaceholder = CHAT_STRINGS.INPUT_PLACEHOLDER_ACTIVE;

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

    if (currentSessionId && (!sessionTimestamp || (now - parseInt(sessionTimestamp, 10)) > SESSION_EXPIRATION_MS)) {
      clearChatSessionData();
      currentSessionId = null;
    }

    if (!currentSessionId) {
      currentSessionId = crypto.randomUUID();
      localStorage.setItem('chat_widget_session', currentSessionId);
    }
    
    localStorage.setItem('chat_widget_session_timestamp', now.toString());
    setSessionId(currentSessionId);
    updateState('start', { main: null, child: null });
  }, []);

  useEffect(() => {
    if (sessionId) syncAndInitialize();
  }, [sessionId]);

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
    try {
      const historyRes = await fetch(`${appUrl}/api/chat/history/${sessionId}`, {
        headers: { 'Accept': 'application/json', 'X-Client-Token': clientToken, 'Content-Type': 'application/json; charset=UTF-8' }
      });

      if (historyRes.status === 401) {
        throw new Error(`Session expired or invalid (Status: ${historyRes.status})`);
      } else if (historyRes.status === 429){
        return;
      } else if (!historyRes.ok) {
        return;
      }
      
      const historyData = historyRes.ok ? await historyRes.json() : { messages: [] };
      
      const formattedHistory = (historyData.messages || []).map(msg => ({
        id: msg.id,
        feedback: msg.feedback,
        text: msg.text,
        sender: msg.sender,
        isApi: msg.sender === 'bot'
      }));
      setMessages([...formattedHistory, { text: CHAT_STRINGS.INITIAL_MESSAGE, sender: 'bot', isApi: false }]);
      setActiveOptions([{ name: 'Filtrar por módulo', value: 'Filtrar por módulo', type: "categories" }]);
    } catch (error) {
      console.warn("Session expired or request failed. Resetting session ID...", error);

      let currentSessionId = crypto.randomUUID();
      localStorage.setItem('chat_widget_session', currentSessionId);
      setSessionId(currentSessionId);
    }
  };

  const sendApiMessage = async (text, mainCat, childCat) => {
    setIsLoading(true);
    lastUserMessageRef.current = text; 

    try {
      setActiveOptions([]);
      const response = await fetch(`${appUrl}/api/chat`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json; charset=UTF-8', 'Accept': 'application/json', 'X-Client-Token': clientToken },
        body: JSON.stringify({
          chatInput: text,
          sessionId,
          mainCategory: mainCat,
          childCategory: childCat
        })
      });
      const data = await response.json();
      if (!data.answer){
        setMessages(prev => [...prev, { text: CHAT_STRINGS.PROCESS_ERROR, sender: 'bot', isApi: false }]);
        setActiveOptions([{ name: 'Tentar novamente', value: text, type: "try-again" }]);
      } else {
        setMessages(prev => [...prev, { id: data.conversationId, feedback: null, text: data.answer, sender: 'bot', isApi: true }]);
      }
    } catch (error) {
      setMessages(prev => [...prev, { text: CHAT_STRINGS.CONNECTION_ERROR, sender: 'bot', isApi: false }]);
      setActiveOptions([{ name: 'Tentar novamente', value: text, type: "try-again" }]);
    } finally {
      setIsLoading(false);
    }
  };

  const runStateEngine = async (text) => {
    localStorage.setItem('chat_widget_session_timestamp', Date.now().toString());

    if (text === 'Tentar novamente') {
      setActiveOptions([]);
      text = lastUserMessageRef.current; 
    }

    const isOptionSelected = activeOptions.some(opt => opt.value === text);

    if (!isOptionSelected && currentStep !== 'completed') {
      updateState('completed', { main: 'Geral', child: 'Geral' });
      setMessages(prev => [...prev, { text: CHAT_STRINGS.DEFAULT_CATEGORY_NOTICE, sender: 'bot', isApi: false }]);
      await sendApiMessage(text, 'Geral', 'Geral');
      return;
    }

    if (currentStep === 'awaiting_main') {
      setActiveOptions([]);
      if (text === 'Geral') {
        updateState('completed', { main: 'Geral', child: 'Geral' });
        setMessages(prev => [...prev, { text: CHAT_STRINGS.GERAL_SELECTED, sender: 'bot', isApi: false }]);
        return;
      }

      const prefix = text.match(/^([0-9]+)/)?.[1];
      if (!prefix) {
        setMessages(prev => [...prev, { text: CHAT_STRINGS.INVALID_CATEGORY_ERROR, sender: 'bot', isApi: false }]);
        return;
      }

      try {
        const res = await fetch(`${appUrl}/api/chat/categories?parent=${prefix}`, {
          headers: { 'X-Client-Token': clientToken, 'Accept': 'application/json' }
        });
        const data = await res.json();
        updateState('awaiting_child', { main: text, child: null });

        const cleanMainName = formatCategoryOption(text).name;
        setMessages(prev => [...prev, { text: CHAT_STRINGS.SUBCATEGORY_PROMPT(cleanMainName), sender: 'bot', isApi: false }]);
        
        const formattedChildCategories = (data.categories || []).map(formatCategoryOption);
        setActiveOptions([{ name: 'Geral', value: 'Geral', type: "option" }, ...formattedChildCategories]);
      } catch (err) {
        console.error(err);
      }
      return;
    }

    if (currentStep === 'awaiting_child') {
      setActiveOptions([]);
      updateState('completed', { main: activeFilters.main, child: text });
      setMessages(prev => [...prev, { text: CHAT_STRINGS.FILTERS_APPLIED, sender: 'bot', isApi: false }]);
      return;
    }

    if (currentStep === 'completed') {
      await sendApiMessage(text, activeFilters.main, activeFilters.child);
    }
  };

  const handleSendMessage = async (type, overrideValue = null, overrideLabel = null) => {
    if (type == "option"){
      setMessages(prev => [...prev, { text: formatCategoryOption(overrideLabel).name, sender: 'user', isApi: false }]);
      await runStateEngine(overrideValue);
    } else if (type == "question"){
      if (!inputValue) return;
      setMessages(prev => [...prev, { text: inputValue, sender: 'user', isApi: false }]);
      setInputValue('');
      await runStateEngine(inputValue);
    } else if (type == "try-again"){
      setMessages(prev => [...prev, { text: overrideValue, sender: 'user', isApi: false }]);
      await runStateEngine(overrideLabel);
    } else if (type == "categories"){
      setActiveOptions([])
      setMessages(prev => [...prev, { text: "Selecione o módulo que você deseja consultar:", sender: 'bot', isApi: false }]);
      setIsLoading(true);
      try{
        const categoriesRes = await fetch(`${appUrl}/api/chat/categories`, {
          headers: { 'X-Client-Token': clientToken, 'Accept': 'application/json', 'Content-Type': 'application/json; charset=UTF-8' }
        });
        const categoriesData = await categoriesRes.json();
        updateState('awaiting_main');
        const formattedMainCategories = (categoriesData.categories || []).map(formatCategoryOption);
        setActiveOptions([{ name: 'Geral', value: 'Geral', type: "option" }, ...formattedMainCategories]);
      } catch (error) {
        setMessages(prev => [...prev, { text: CHAT_STRINGS.CONNECTION_ERROR, sender: 'bot', isApi: false }]);
        handleReset()
      } finally {
        setIsLoading(false);
      }
    }

    return;
  };

  const handleReset = () => {
    updateState('start', { main: null, child: null });
    setMessages([]);
    setActiveOptions([]);
    lastUserMessageRef.current = '';
    syncAndInitialize();
  };

  return {
    isOpen, setIsOpen,
    messages, isLoading, activeOptions,
    inputValue, setInputValue,
    isInputDisabled, inputPlaceholder,
    currentStep, handleSendMessage, handleReset
  };
}