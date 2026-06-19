import axios from 'axios';

// Default country code for numbers without one (India = 91).
// Override via <meta name="default-country-code" content="91"> in app.blade if needed.
const DEFAULT_CC = '91';

export function normalizePhone(raw) {
  if (!raw) return '';
  let n = String(raw).replace(/[^\d+]/g, '');
  if (n.startsWith('+')) n = n.slice(1);
  if (n.startsWith('00')) n = n.slice(2);
  // If 10 digits assume local — prepend country code.
  if (n.length === 10) n = DEFAULT_CC + n;
  return n;
}

export function telHref(raw) {
  const n = normalizePhone(raw);
  return n ? `tel:+${n}` : '';
}

export function waHref(raw, message = '') {
  const n = normalizePhone(raw);
  if (!n) return '';
  const url = new URL(`https://wa.me/${n}`);
  if (message) url.searchParams.set('text', message);
  return url.toString();
}

// Log click as an activity (fire-and-forget). Won't block the link navigation.
export function logContactActivity(leadId, type, phone) {
  if (!leadId) return;
  axios.post(`/api/leads/${leadId}/activities`, {
    type, // 'call' | 'whatsapp'
    title: type === 'whatsapp' ? `WhatsApp opened (${phone})` : `Call initiated (${phone})`,
    body: null,
  }).catch(() => { /* ignore — UX should not break if log fails */ });
}
