import React from 'react';

export default function ChatHeader({ currentStep, onReset, onClose }) {
  return (
    <div className="bg-blue-600 text-white p-4 font-bold flex justify-between items-center">
      <span>Assistente Virtual</span>
      <div className="flex items-center gap-3">
        {currentStep === 'completed' && (
          <button onClick={onReset} className="cursor-pointer bg-white/15 text-white border border-white/30 px-2 py-1 rounded text-xs">
            Alterar Categoria
          </button>
        )}
        <span onClick={onClose} className="cursor-pointer text-lg">✕</span>
      </div>
    </div>
  );
}