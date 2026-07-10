import React from 'react';

export default function ChatHeader({ currentStep, onReset, onClose }) {
  return (
    <div className="cb:bg-blue-600 cb:text-white cb:p-4 cb:font-bold cb:flex cb:justify-between cb:items-center">
      <span>Assistente Virtual</span>
      <div className="cb:flex cb:items-center cb:gap-3">
        {currentStep === 'completed' && (
          <button onClick={onReset} className="cb:cursor-pointer cb:bg-white/15 cb:text-white cb:border cb:border-white/30 cb:px-2 cb:py-1 cb:rounded cb:text-xs">
            Alterar Categoria
          </button>
        )}
        <span onClick={onClose} className="cb:cursor-pointer cb:text-lg">✕</span>
      </div>
    </div>
  );
}