import React, { useState, useEffect, useRef } from 'react';

export default function MessageFeedback({ appUrl, clientToken, conversationId }) {
  const [hasVoted, setHasVoted] = useState(false);
  const [isSending, setIsSending] = useState(false);

  const sendFeedback = async (type) => {
    if (isSending || hasVoted) return;
    
    setIsSending(true);
    try {
      const response = await fetch(`${appUrl}/api/chat/feedback`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Client-Token': clientToken
        },
        body: JSON.stringify({
          conversationId: conversationId,
          rating: type,
        })
      });

      setHasVoted(true);
    } catch (error) {
      console.error("Failed to submit feedback:", error);
      setHasVoted(true); 
    } finally {
      setIsSending(false);
    }
  };

  if (hasVoted) {
    return null;
  }

  return (
    <div className="twbot-flex twbot-justify-end twbot-gap-2 twbot-mt-1.5 twbot-pt-1 twbot-border-t twbot-border-slate-300/50">
      <button 
        onClick={() => sendFeedback('positive')} 
        disabled={isSending}
        className={`twbot-text-xs twbot-transition-opacity ${isSending ? 'twbot-opacity-30 twbot-cursor-not-allowed' : 'twbot-cursor-pointer twbot-opacity-60 hover:twbot-opacity-100'}`} 
        title="Útil"
      >
        👍
      </button>
      <button 
        onClick={() => sendFeedback('negative')} 
        disabled={isSending}
        className={`twbot-text-xs twbot-transition-opacity ${isSending ? 'twbot-opacity-30 twbot-cursor-not-allowed' : 'twbot-cursor-pointer twbot-opacity-60 hover:twbot-opacity-100'}`} 
        title="Não foi útil"
      >
        👎
      </button>
    </div>
  );
}