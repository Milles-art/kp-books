import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api, money } from '../lib/api';
import BookCover from '../components/BookCover';

export function BookCard({ b }) {
  const open = b.access.allowed_pages;
  return (
    <Link className="card" to={`/books/${b.slug}`}>
      <BookCover book={b} />
      <h3>{b.title}</h3>
      <div className="muted small">{b.subtitle}</div>
      <div className="meta"><span>{b.total_pages} pages</span><span>Full price {money(b.price_full)}</span></div>
      {b.access.credit_balance > 0
        ? <><div className="bar"><i style={{ width: `${(open / b.total_pages) * 100}%` }} /></div><div className="muted small">{open} of {b.total_pages} pages open</div></>
        : <div className="from">Start from {money(b.min_payment)}</div>}
    </Link>
  );
}

export default function Library() {
  const [books, setBooks] = useState(null);
  const [err, setErr] = useState('');
  const [q, setQ] = useState('');

  useEffect(() => { api('/books').then((r) => setBooks(r.data)).catch((e) => setErr(e.message)); }, []);

  const shown = (books || []).filter((b) => `${b.title} ${b.subtitle || ''}`.toLowerCase().includes(q.toLowerCase()));
  return (
    <>
      <section className="hero">
        <h1>Read first. Pay what you can.</h1>
        <p>Open as many pages as your payment covers, from 1,000 TZS. Books are read here only, so there is nothing to download.</p>
        <input className="search" type="search" placeholder="Search books" value={q} onChange={(e) => setQ(e.target.value)} aria-label="Search books" />
      </section>
      {err && <p className="err">{err}</p>}
      {!books && !err && <p className="muted">Loading books…</p>}
      <div className="grid">{shown.map((b) => <BookCard key={b.slug} b={b} />)}</div>
      {books && !shown.length && <p className="muted">No books found.</p>}
    </>
  );
}
