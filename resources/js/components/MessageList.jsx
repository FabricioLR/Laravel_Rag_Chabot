import React, { useEffect, useRef } from 'react';
import MessageFeedback from './MessageFeedback';
import { formatBotResponse } from './utils/formatters';

export default function MessageList({ messages, isLoading, activeOptions, onOptionClick, appUrl, clientToken, isOpen }) {
  const messagesEndRef = useRef(null);

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [activeOptions, isOpen]);

  return (
    <div className="flex-1 p-4 overflow-y-auto bg-slate-50 flex flex-col gap-3">
      {messages.map((msg, index) => (
        <div 
          key={index} 
          className={`p-2.5 max-w-[80%] rounded-lg leading-relaxed ${
            msg.sender === 'user' ? 'bg-slate-200 text-slate-800 self-end ml-auto' : 'bg-slate-200 text-slate-800 self-start'
          }`}
        >
          <div className="max-w-xs md:max-w-md p-3 rounded-lg overflow-hidden break-words whitespace-pre-wrap bg-gray-100">
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
            <button key={i} onClick={() => onOptionClick(opt.value ? opt.value : opt.name)} className="cursor-pointer bg-white text-blue-600 border border-blue-600 px-3 py-1.5 rounded-full font-medium text-xs hover:bg-blue-600 hover:text-white transition-all">
              {opt.name}
            </button>
          ))}
        </div>
      )}
      <div ref={messagesEndRef} />
    </div>
  );
}