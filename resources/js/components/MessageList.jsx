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
      className="cb:flex-1 cb:p-4 cb:overflow-y-auto cb:bg-slate-50 cb:flex cb:flex-col cb:gap-3 cb:relative"
    >
      {messages.map((msg, index) => {
        const isLast = index === messages.length - 1;

        return (
          <div 
            key={index} 
            ref={isLast ? lastMessageRef : null}
            className={`cb:p-2.5 cb:max-w-[80%] cb:rounded-lg cb:leading-relaxed ${
              msg.sender === 'user' ? 'cb:bg-slate-200 cb:text-slate-800 cb:self-end cb:ml-auto' : 'cb:bg-slate-200 cb:text-slate-800 cb:self-start'
            }`}
          >
            <div className="cb:max-w-xs cb:md:max-w-md cb:p-3 cb:rounded-lg cb:overflow-hidden cb:break-words cb:whitespace-pre-wrap cb:bg-gray-100">
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
        );
      })}

      {isLoading && (
        <div className="cb:bg-slate-200 cb:text-slate-800 cb:self-start cb:p-3 cb:rounded-lg cb:flex cb:gap-1">
          <span className="cb:w-1.5 cb:h-1.5 cb:bg-slate-500 cb:rounded-full cb:animate-bounce"></span>
          <span className="cb:w-1.5 cb:h-1.5 cb:bg-slate-500 cb:rounded-full cb:animate-bounce cb:[animation-delay:0.2s]"></span>
          <span className="cb:w-1.5 cb:h-1.5 cb:bg-slate-500 cb:rounded-full cb:animate-bounce cb:[animation-delay:0.4s]"></span>
        </div>
      )}

      {activeOptions.length > 0 && (
        <div className="cb:flex cb:flex-wrap cb:gap-2 cb:mt-2 cb:max-w-[90%] cb:self-start">
          {activeOptions.map((opt, i) => (
            <button key={i} onClick={() => onOptionClick(opt.value ? opt.value : opt.name)} className="cb:cursor-pointer cb:bg-white cb:text-blue-600 cb:border cb:border-blue-600 cb:px-3 cb:py-1.5 cb:rounded-full cb:font-medium  cb:hover:bg-blue-600 cb:hover:text-white cb:transition-all">
              {opt.name}
            </button>
          ))}
        </div>
      )}
      
      <div ref={messagesEndRef} />
    </div>
  );
}