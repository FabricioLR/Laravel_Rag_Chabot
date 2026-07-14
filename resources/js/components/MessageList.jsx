import React, { useEffect, useRef } from 'react';
import MessageFeedback from './MessageFeedback';
import { formatBotResponse } from './utils/formatters';

export default function MessageList({ messages, isLoading, activeOptions, onOptionClick, appUrl, clientToken, isOpen }) {
  const containerRef = useRef(null);
  const messagesEndRef = useRef(null);
  const lastMessageRef = useRef(null);
  const isInitialRender = useRef(true);

  useEffect(() => {
    if (isOpen) {
      messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }
  }, [isOpen]);

  useEffect(() => {
    if (isInitialRender.current) {
      isInitialRender.current = false;
      return;
    }

    if (lastMessageRef.current && containerRef.current) {
      const container = containerRef.current;
      const lastMessage = lastMessageRef.current;
      const targetScrollTop = lastMessage.offsetTop - 10;

      container.scrollTo({
        top: targetScrollTop,
        behavior: 'smooth'
      });
    }
  }, [messages, activeOptions]);

  return (
    <div 
      ref={containerRef}
      className="cb:flex-1 cb:p-4 cb:overflow-y-auto cb:bg-[#f4f8fc] cb:flex cb:flex-col cb:gap-4 cb:relative"
    >
      {messages.map((msg, index) => {
        const isLast = index === messages.length - 1;
        const isUser = msg.sender === 'user';

        return (
          <div 
            key={index} 
            ref={isLast ? lastMessageRef : null}
            className={`cb:flex cb:flex-col cb:max-w-[85%] ${isUser ? 'cb:self-end cb:items-end' : 'cb:self-start cb:items-start'}`}
          >
            <div className={`cb:p-3 cb:rounded-xl cb:text-sm cb:leading-relaxed cb:shadow-sm cb:border ${
              isUser 
                ? 'cb:bg-[#e2edf8] cb:text-[#0a3a60] cb:border-[#cbdff2]' 
                : 'cb:bg-white cb:text-[#2c3e50] cb:border-[#e2edf8]'
            }`}>
              <div className="cb:overflow-hidden cb:break-words cb:whitespace-pre-wrap">
                {msg.sender === 'bot' ? (
                  <span dangerouslySetInnerHTML={{ __html: formatBotResponse(msg.text) }} />
                ) : (
                  msg.text
                )}
              </div>
              
              {msg.sender === 'bot' && msg.isApi && msg.feedback === null && (
                <MessageFeedback appUrl={appUrl} clientToken={clientToken} conversationId={msg.id} />
              )}
            </div>
          </div>
        );
      })}

      {isLoading && (
        <div className="cb:bg-white cb:border cb:border-[#e2edf8] cb:self-start cb:p-3 cb:rounded-xl cb:flex cb:gap-1.5 cb:shadow-sm">
          <span className="cb:w-2 cb:h-2 cb:bg-[#0054a6] cb:rounded-full cb:animate-bounce"></span>
          <span className="cb:w-2 cb:h-2 cb:bg-[#0054a6] cb:rounded-full cb:animate-bounce cb:[animation-delay:0.2s]"></span>
          <span className="cb:w-2 cb:h-2 cb:bg-[#0054a6] cb:rounded-full cb:animate-bounce cb:[animation-delay:0.4s]"></span>
        </div>
      )}

      {activeOptions.length > 0 && (
        <div className="cb:flex cb:flex-wrap cb:gap-2 cb:mt-1 cb:max-w-[95%] cb:self-start">
          {activeOptions.map((opt, i) => (
            <button 
              key={i} 
              onClick={() => onOptionClick(opt.value, opt.name)} 
              className="cb:cursor-pointer cb:bg-[#e2edf8] cb:text-[#004b93] cb:border cb:border-[#c9dfef] cb:px-3 cb:py-1.5 cb:rounded-lg cb:text-xs cb:font-semibold cb:uppercase cb:tracking-wider cb:hover:bg-[#0054a6] cb:hover:text-white cb:hover:border-[#0054a6] cb:transition-all cb:shadow-sm"
            >
              {opt.name}
            </button>
          ))}
        </div>
      )}
      
      <div ref={messagesEndRef} />
    </div>
  );
}