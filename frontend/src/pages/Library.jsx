import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api, money } from '../lib/api';
import BookCover from '../shared/ui/BookCover';
import { Icon } from '../shared/ui/Icon';

export function BookCard({ b, i = 0 }) {
  const open = b.access.allowed_pages;
  const paid = b.access.credit_balance > 0;
  return (
    <Link className={`bcard c${i % 4}`} to={`/books/${b.slug}`}>
      <span className="idx">{String(i + 1).padStart(2, '0')}</span>
      <div className="bcover"><BookCover book={b} /></div>
      <div className="bfoot">
        <div>
          <small>{paid ? `${open} of ${b.total_pages} pages open` : `${b.total_pages} pages, from ${money(b.min_payment)}`}</small>
          <h3>{b.title}</h3>
        </div>
        <span className="circ"><Icon n="arrow" size={18} /></span>
      </div>
    </Link>
  );
}

const T = 'READ FIRST ◆ PAY WHAT YOU CAN ◆ MADE IN TANZANIA ◆ '.repeat(6);
const STEPS = [
  ['01', 'Choose a book', 'Read the free preview pages first, no payment needed.'],
  ['02', 'Pay what you can', 'From 1,000 TZS. Your payment opens pages in order.'],
  ['03', 'Keep reading', 'Top up any time to open more. Nothing to download.'],
];

export default function Library() {
  const [books, setBooks] = useState(null);
  const [err, setErr] = useState('');
  const [q, setQ] = useState('');

  useEffect(() => { api('/books').then((r) => setBooks(r.data)).catch((e) => setErr(e.message)); }, []);

  const shown = (books || []).filter((b) => `${b.title} ${b.subtitle || ''}`.toLowerCase().includes(q.toLowerCase()));
  return (
    <>
      <section className="hero">
        <div className="wrap">
          <div className="hero-top"><span className="eyebrow dot">New / KP Book</span><span className="eyebrow k">Dar es Salaam, TZ</span></div>
          <div className="hero-main">
            <h1><span className="hl">Read first.</span><span className="hr">Pay what you can.</span></h1>
            <div className="stack" aria-hidden="true">
              <i className="shape tri-g" /><i className="shape sq-g" /><i className="shape tri-r" />
              {(books || []).slice(0, 3).map((b) => <BookCover key={b.slug} book={b} />)}
            </div>
          </div>
          <div className="hero-foot">
            <p>Open as many pages as your payment covers, from 1,000 TZS. Books are read here only, so there is nothing to download.</p>
            <div className="btns">
              <a className="btn" href="#shelf">Browse books <Icon n="arrow" size={16} /></a>
              <a className="btn green" href="#how">How it works</a>
            </div>
          </div>
        </div>
      </section>

      <div className="ribbon" aria-hidden="true"><span>{T}{T}</span></div>

      <section className="wrap" id="shelf">
        <div className="sec-head">
          <div><span className="eyebrow">Section 01 / The shelf</span><h2>Pick a story</h2></div>
          <input className="search" type="search" placeholder="Search books" value={q} onChange={(e) => setQ(e.target.value)} aria-label="Search books" />
        </div>
        {err && <p className="err">{err}</p>}
        {!books && !err && <p className="muted">Loading books…</p>}
        <div className="shelf">{shown.map((b, i) => <BookCard key={b.slug} b={b} i={i} />)}</div>
        {books && !shown.length && <p className="muted">No books found.</p>}
      </section>

      <section className="story" id="how">
        <div className="wrap">
          <span className="eyebrow">Section 02 / How it works</span>
          <h2>Your pages. Your pace.</h2>
          <div className="steps">
            {STEPS.map(([n, t, d]) => <div className="step" key={n}><b>{n}</b><h3>{t}</h3><p>{d}</p></div>)}
          </div>
        </div>
      </section>
    </>
  );
}
