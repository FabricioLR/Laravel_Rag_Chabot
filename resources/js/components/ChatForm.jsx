import React from 'react';

export default function ChatForm({ inputValue, setInputValue, isDisabled, placeholder, onSubmit }) {
  const handleSubmit = (e) => {
    e.preventDefault();
    onSubmit("question");
  };

  return (
    <form onSubmit={handleSubmit} className="cb:flex cb:border-t cb:border-[#e2edf8] cb:p-3 cb:bg-white cb:gap-2">
      <input 
        type="text" 
        value={inputValue} 
        onChange={(e) => setInputValue(e.target.value)} 
        disabled={isDisabled} 
        placeholder={placeholder} 
        className="cb:flex-1 cb:border cb:border-[#d2e2f0] cb:p-2.5 cb:rounded-lg cb:outline-none cb:focus:border-[#0054a6] cb:focus:ring-1 cb:focus:ring-[#0054a6] cb:disabled:bg-[#f4f8fc] cb:text-sm cb:placeholder:cb:text-slate-400" 
        autoComplete="off" 
      />
      <button 
        type="submit" 
        disabled={isDisabled} 
        className="cb:cursor-pointer cb:bg-[#0054a6] cb:text-white cb:px-4 cb:py-2.5 cb:rounded-lg cb:font-medium cb:disabled:cursor-not-allowed cb:disabled:bg-slate-200 cb:disabled:text-slate-400 cb:text-sm cb:hover:bg-[#004285] cb:transition-colors"
      >
        Enviar
      </button>
    </form>
  );
}