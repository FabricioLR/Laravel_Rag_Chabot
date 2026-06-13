import React from 'react';

export default function ChatForm({ inputValue, setInputValue, isDisabled, placeholder, onSubmit }) {
  const handleSubmit = (e) => {
    e.preventDefault();
    onSubmit();
  };

  return (
    <form onSubmit={handleSubmit} className="flex border-t border-slate-200 p-2.5 bg-white gap-2">
      <input 
        type="text" 
        value={inputValue} 
        onChange={(e) => setInputValue(e.target.value)} 
        disabled={isDisabled} 
        placeholder={placeholder} 
        className="flex-1 border border-slate-300 p-2 rounded-md outline-none focus:border-blue-500 disabled:bg-slate-100 text-sm" 
        autoComplete="off" 
      />
      <button 
        type="submit" 
        disabled={isDisabled || !inputValue.trim()} 
        className="cursor-pointer bg-blue-600 text-white px-3 py-2 rounded-md font-medium disabled:cursor-not-allowed disabled:bg-slate-300 text-sm"
      >
        Enviar
      </button>
    </form>
  );
}