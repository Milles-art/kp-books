import { useCallback, useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { api, money } from '../lib/api';
import { BookCover } from '@kp/shared';
import PayModal from '../components/PayModal';

export default function BookDetail() {
  const { slug } = useParams();
  const nav = useNavigate();
  const [book, setBook] = useState(null);
  const [err, setErr] = useState('');
  const [paying, setPaying] = useState(false);

  const load = useCallback(() => api(`/books/${slug}`).then((r) => setBook(r.data)).catch((e) => setErr(e.status === 404 ? 'Book not found.' : e.message)), [slug]);
  useEffect(() => { load(); }, [load]);

  if (err) return <p className="err">{err}</p>;
  if (!book) return <p className="muted">Loading…</p>;

  const { access } = book;
  const owned = access.remaining_price === 0;
  const share = `https://wa.me/?text=${encodeURIComponent(`Read the first pages of “${book.title}” free on KP Book: ${window.location.href}`)}`;

  return (
    <>
      <p><Link className="btn ghost sm" to="/">Back to all books</Link></p>
      <div className="detail">
        <BookCover book={book} />
        <div>
          <span className="eyebrow">KP Book{book.author ? ` / ${book.author}` : ''}</span>
          <h1>{book.title}</h1>
          <p className="muted">{book.subtitle}{book.author ? `, by ${book.author}` : ''}</p>
          <p className="lit">{book.description}</p>
          <div className="stats">
            <div><b>{book.total_pages}</b>pages</div>
            <div><b>{(book.total_pages * 1.25 / 60).toFixed(1)} h</b>reading time</div>
            <div><b>{money(book.price_full)}</b>whole book</div>
          </div>
          <div className="bar"><i style={{ width: `${(access.allowed_pages / book.total_pages) * 100}%` }} /></div>
          <div className="muted small">{access.allowed_pages} of {book.total_pages} pages are open to you</div>
          <div className="acts">
            <button className="btn" onClick={() => nav(`/read/${book.slug}/${access.last_page || 1}`)}>
              {access.last_page > 1 ? 'Continue reading' : access.credit_balance > 0 ? 'Start reading' : 'Read free preview'}
            </button>
            {!owned && <button className="btn green" onClick={() => setPaying(true)}>Unlock more pages</button>}
            <a className="btn ghost" href={share} target="_blank" rel="noopener noreferrer">Share on WhatsApp</a>
          </div>
          {!owned && (
            <>
              <h3 className="sub">What your payment opens</h3>
              <table className="tbl">
                <thead><tr><th>You pay</th><th>You can read</th></tr></thead>
                <tbody>
                  {book.options.map((o) => (
                    <tr key={o.amount}><td>{money(o.amount)}</td><td>{o.whole_book ? `The whole book (${book.total_pages} pages)` : `Pages 1 to ${o.allowed_pages_after}`}</td></tr>
                  ))}
                </tbody>
              </table>
              <p className="muted small">Top up any time. New payments open the next pages and you keep what you already paid for.</p>
            </>
          )}
        </div>
      </div>
      {paying && <PayModal book={book} onClose={() => setPaying(false)} onPaid={() => { setPaying(false); load(); }} />}
    </>
  );
}
