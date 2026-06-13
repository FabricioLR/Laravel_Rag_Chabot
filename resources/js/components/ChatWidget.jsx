import React from 'react';
import { useChatEngine } from './hooks/useChatEngine';
import ChatHeader from './ChatHeader';
import MessageList from './MessageList';
import ChatForm from './ChatForm';

export default function ChatWidget({ appUrl, clientToken }) {
  const chat = useChatEngine(appUrl, clientToken);

  return (
    <div className="fixed bottom-5 right-5 z-[999999] font-sans">
      {chat.isOpen ? (
        <div className="flex flex-col w-[350px] h-[450px] bg-white rounded-xl shadow-2xl overflow-hidden mb-4 border border-slate-100">
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
          className="w-[60px] h-[60px] bg-blue-600 rounded-full flex items-center justify-center cursor-pointer shadow-xl hover:scale-105 transition-transform"
        >
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
          </svg>
        </div>
      )}
    </div>
  );
}