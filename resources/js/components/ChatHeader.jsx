import React from 'react';

export default function ChatHeader({ currentStep, onReset, onClose }) {
  return (
    <div className="cb:bg-[#0054a6] cb:text-white cb:p-4 cb:font-semibold cb:flex cb:justify-between cb:items-center cb:border-b cb:border-white/10">
      <span className="cb:tracking-wide cb:text-sm">Assistente Virtual</span>
      <div className="cb:flex cb:items-center cb:gap-3">
        {currentStep === 'completed' && (
          <button onClick={onReset} className="cb:cursor-pointer cb:bg-white/10 cb:text-white cb:border cb:border-white/20 cb:px-2.5 cb:py-1 cb:rounded-md cb:text-xs cb:hover:bg-white/20 cb:transition-colors">
            Alterar Categoria
          </button>
        )}
        <span onClick={onClose} className="cb:cursor-pointer cb:text-xl cb:opacity-80 cb:hover:opacity-100 cb:transition-opacity">✕</span>
      </div>
    </div>
  );
}