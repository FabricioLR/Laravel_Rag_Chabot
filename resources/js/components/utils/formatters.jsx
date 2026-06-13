export function formatBotResponse(rawText) {
  if (!rawText) return '';
  let formatted = rawText;

  try {
    formatted = JSON.parse(`"${formatted.replace(/"/g, '\\"')}"`);
  } catch (e) {
  }

  formatted = formatted.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, (match, text, url) => {
    return `<a href="${url.replace(/\\/g, '')}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:underline font-medium">${text}</a>`;
  });

  formatted = formatted.replace(/<(https?:\/\/[^>]+)>/g, (match, url) => {
    const cleanUrl = url.replace(/\\/g, '');
    return `<a href="${cleanUrl}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:underline font-medium">${cleanUrl}</a>`;
  });

  formatted = formatted.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');

  return formatted.replace(/\n/g, '<br>');
}