import { useEffect, useRef, useState } from 'react';
import { api, money, LOGIN_URL, SIMULATE } from '../lib/api';

const PROVIDERS = [['mpesa', 'M-Pesa'], ['airtel', 'Airtel Money'], ['halopesa', 'HaloPesa'], ['mixx', 'Mixx by Yas'], ['card', 'Card']];
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

export default function PayModal({ book, onClose, onPaid }) {
  const remaining = book.access.remaining_price;
  const min = Math.min(book.min_payment, remaining);
  const [amount, setAmount] = useState(min);
  const [provider, setProvider] = useState('mpesa');
  const [quote, setQuote] = useState(null);
  const [state, setState] = useState('idle'); // idle | paying | waiting
  const [msg, setMsg] = useState('');
  const alive = useRef(true);
  useEffect(() => () => { alive.current = false; }, []);

  useEffect(() => {
    if (!amount) return;
    const t = setTimeout(() => {
      api(`/books/${book.slug}/quote?amount=${amount}`)
        .then((r) => setQuote(r.data))
        .catch((e) => setMsg(e.status === 401 ? 'login' : e.message));
    }, 300);
    return () => clearTimeout(t);
  }, [amount, book.slug]);

  const chips = [...new Set([book.min_payment, book.min_payment * 2, book.min_payment * 5, remaining])]
    .filter((v) => v > 0 && v <= remaining).sort((a, b) => a - b);

  async function pay() {
    setState('paying'); setMsg('');
    try {
      const { data } = await api(`/books/${book.slug}/payments`, { method: 'POST', body: { amount: Number(amount), provider } });
      if (SIMULATE) {
        await api(`/payments/${data.id}/simulate-confirm`, { method: 'POST' });
        return onPaid();
      }
      setState('waiting'); // the reader approves on their phone; poll until pages open
      for (let i = 0; i < 40 && alive.current; i++) {
        await sleep(3000);
        const r = await api(`/books/${book.slug}`);
        if (r.data.access.allowed_pages > book.access.allowed_pages) return onPaid();
      }
      if (alive.current) { setState('idle'); setMsg('We have not received the payment yet. If you paid, your pages will open shortly.'); }
    } catch (e) {
      setState('idle'); setMsg(e.status === 401 ? 'login' : e.message);
    }
  }

  const ok = Boolean(quote?.valid) && Number(amount) >= min;
  return (
    <div className="ov" onClick={(e) => e.target === e.currentTarget && onClose()}>
      <div className="modal" role="dialog" aria-modal="true" aria-label="Unlock pages">
        <div className="mh"><h2>Unlock pages</h2><button className="x" onClick={onClose} aria-label="Close">×</button></div>
        <p className="muted">{book.title}, whole book {money(book.price_full)}</p>
        <div className="chips">
          {chips.map((v) => (
            <button key={v} className="chip" onClick={() => setAmount(v)}>{v === remaining ? 'Rest of book ' : ''}{v.toLocaleString('en-US')}</button>
          ))}
        </div>
        <label>Amount in TZS (minimum {min.toLocaleString('en-US')})
          <input type="number" inputMode="numeric" value={amount} onChange={(e) => setAmount(Math.max(0, Math.floor(+e.target.value) || 0))} />
        </label>
        <div className="pv">
          {quote?.valid ? (quote.whole_book ? 'This opens the whole book.' : `This opens pages 1 to ${quote.allowed_pages_after}.`) : `Enter at least ${money(min)}.`}
        </div>
        <label>Pay with
          <select value={provider} onChange={(e) => setProvider(e.target.value)}>
            {PROVIDERS.map(([v, l]) => <option key={v} value={v}>{l}</option>)}
          </select>
        </label>
        {msg === 'login' ? <p className="err">Please <a href={LOGIN_URL}>log in</a> to pay.</p> : msg && <p className="err">{msg}</p>}
        {state === 'waiting' && <p className="muted">Approve the payment on your phone. Your pages open as soon as it is confirmed.</p>}
        <button className="btn full" disabled={!ok || state !== 'idle'} onClick={pay}>
          {state === 'idle' ? `Pay ${money(Math.min(amount, remaining))}` : 'Please wait'}
        </button>
        {SIMULATE && <p className="muted small center">Demo only. No real money moves.</p>}
      </div>
    </div>
  );
}
