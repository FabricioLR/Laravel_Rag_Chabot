import React from 'react';
import { useChatEngine } from './hooks/useChatEngine';
import ChatHeader from './ChatHeader';
import MessageList from './MessageList';
import ChatForm from './ChatForm';

export default function ChatWidget({ appUrl, clientToken }) {
  const chat = useChatEngine(appUrl, clientToken);

  return (
    <div className="cb:fixed cb:bottom-5 cb:right-5 cb:z-[999999] cb:font-sans">
      {chat.isOpen ? (
        <div className="cb:flex cb:flex-col cb:w-[350px] cb:h-[450px] cb:bg-white cb:rounded-xl cb:shadow-2xl cb:overflow-hidden cb:mb-4 cb:border cb:border-slate-100">
          <ChatHeader 
            currentStep={chat.currentStep} 
            onReset={chat.handleReset} 
            onClose={() => chat.setIsOpen(false)} 
          />

          <MessageList 
            messages={chat.messages} 
            isLoading={chat.isLoading} 
            activeOptions={chat.activeOptions} 
            onOptionClick={chat.handleSendMessage} 
            appUrl={appUrl} 
            clientToken={clientToken} 
            isOpen={chat.isOpen}
          />

          <ChatForm 
            inputValue={chat.inputValue} 
            setInputValue={chat.setInputValue} 
            isDisabled={chat.isInputDisabled} 
            placeholder={chat.inputPlaceholder} 
            onSubmit={chat.handleSendMessage} 
          />
        </div>
      ) : (
        <div 
          onClick={() => chat.setIsOpen(true)} 
          className="cb:w-[60px] cb:h-[60px] cb:bg-blue-600 cb:rounded-full cb:flex cb:items-center cb:justify-center cb:cursor-pointer cb:shadow-xl cb:hover:scale-105 cb:transition-transform"
        >
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
          </svg>
        </div>
      )}
    </div>
  );
}