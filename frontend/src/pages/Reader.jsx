import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { api } from '../lib/api';
import PayModal from '../components/PayModal';

const sizeKey = 'kpbook.fontSize';
function savedSize() {
  try { return Number(localStorage.getItem(sizeKey)) || 19; } catch { return 19; }
}

export default function Reader() {
  const { slug, page } = useParams();
  const n = Math.max(1, parseInt(page, 10) || 1);
  const nav = useNavigate();
  const [book, setBook] = useState(null);
  const [view, setView] = useState({ loading: true });
  const [size, setSize] = useState(savedSize);
  const [paying, setPaying] = useState(false);
  const [tick, setTick] = useState(0);

  const loadBook = useCallback(() => api(`/books/${slug}`).then((r) => setBook(r.data)).catch(() => {}), [slug]);
  useEffect(() => { loadBook(); }, [loadBook]);

  useEffect(() => {
    let on = true;
    setView({ loading: true });
    api(`/books/${slug}/pages/${n}`)
      .then((r) => on && setView({ page: r.data }))
      .catch((e) => on && setView(e.status === 403 ? { lock: e.data } : { error: e.status === 404 ? 'This page does not exist.' : e.message }));
    return () => { on = false; };
  }, [slug, n, tick]);

  const go = (p) => { window.scrollTo(0, 0); nav(`/read/${slug}/${p}`); };
  const bump = (d) => setSize((s) => { const v = Math.min(26, Math.max(14, s + d * 2)); try { localStorage.setItem(sizeKey, String(v)); } catch { /* ignore */ } return v; });

  // Faint watermark with the reader's masked phone or email, drawn over every page.
  const wm = useMemo(() => {
    const text = view.page?.watermark || 'Preview';
    const svg = `<svg xmlns='http://www.w3.org/2000/svg' width='280' height='170'><text x='10' y='95' transform='rotate(-24 140 85)' font-family='sans-serif' font-size='15' fill='gray' fill-opacity='.2'>KP Book ${text}</text></svg>`;
    return `url("data:image/svg+xml;utf8,${encodeURIComponent(svg)}")`;
  }, [view.page]);

  const total = view.page?.total_pages || view.lock?.total_pages || book?.total_pages || n;
  const blocks = view.page ? view.page.content.split(/\n\n+/) : [];

  return (
    <>
      <div className="rtools">
        <Link className="btn ghost sm" to={`/books/${slug}`}>Back to book</Link>
        <div className="rt"><b>{book?.title}</b><span className="muted small">Page {n} of {total}</span></div>
        <button className="btn ghost sm" onClick={() => bump(-1)} aria-label="Smaller text">A−</button>
        <button className="btn ghost sm" onClick={() => bump(1)} aria-label="Larger text">A+</button>
      </div>
      <div className="bar"><i style={{ width: `${(n / total) * 100}%` }} /></div>

      <div className="sheet" style={{ fontSize: size }} onContextMenu={(e) => e.preventDefault()} onCopy={(e) => e.preventDefault()} onDragStart={(e) => e.preventDefault()}>
        {view.loading && <p className="muted">Loading…</p>}
        {view.error && <p className="err">{view.error}</p>}
        {view.page && blocks.map((b, i) => (b.startsWith('## ') ? <h3 key={i}>{b.slice(3)}</h3> : <p key={i}>{b}</p>))}
        {view.lock && (
          <div className="lockbox">
            <div className="lk" aria-hidden="true">🔒</div>
            <h3>Page {n} is locked</h3>
            <p>You have unlocked pages 1 to {view.lock.allowed_pages} of {view.lock.total_pages}. Pay any amount from {view.lock.min_payment.toLocaleString('en-US')} TZS to keep reading.</p>
            <button className="btn" onClick={() => setPaying(true)} disabled={!book}>Unlock more pages</button>
          </div>
        )}
        <div className="wm" style={{ backgroundImage: wm }} />
      </div>

      <div className="pager">
        <button className="btn ghost" disabled={n <= 1} onClick={() => go(n - 1)}>Previous</button>
        <input type="number" min="1" max={total} defaultValue={n} key={n} aria-label="Go to page" onKeyDown={(e) => e.key === 'Enter' && go(Math.min(total, Math.max(1, +e.target.value || 1)))} />
        <button className="btn" disabled={n >= total} onClick={() => go(n + 1)}>Next</button>
      </div>

      {paying && book && (
        <PayModal book={book} onClose={() => setPaying(false)} onPaid={() => { setPaying(false); loadBook(); setTick((t) => t + 1); }} />
      )}
    </>
  );
}
