import React from 'react';

export default function ChatForm({ inputValue, setInputValue, isDisabled, placeholder, onSubmit }) {
  const handleSubmit = (e) => {
    e.preventDefault();
    onSubmit();
  };

  return (
    <form onSubmit={handleSubmit} className="cb:flex cb:border-t cb:border-slate-200 cb:p-2.5 cb:bg-white cb:gap-2">
      <input 
        type="text" 
        value={inputValue} 
        onChange={(e) => setInputValue(e.target.value)} 
        disabled={isDisabled} 
        placeholder={placeholder} 
        className="cb:flex-1 cb:border cb:border-slate-300 cb:p-2 cb:rounded-md cb:outline-none cb:focus:border-blue-500 cb:disabled:bg-slate-100 cb:text-sm" 
        autoComplete="off" 
      />
      <button 
        type="submit" 
        disabled={isDisabled || !inputValue.trim()} 
        className="cb:cursor-pointer cb:bg-blue-600 cb:text-white cb:px-3 cb:py-2 cb:rounded-md cb:font-medium cb:disabled:cursor-not-allowed cb:disabled:bg-slate-300 cb:text-sm"
      >
        Enviar
      </button>
    </form>
  );
}