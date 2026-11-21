import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api, LOGIN_URL } from '../lib/api';
import { BookCard } from './Library';

export default function MyLibrary() {
  const [books, setBooks] = useState(null);
  const [err, setErr] = useState('');

  useEffect(() => { api('/library').then((r) => setBooks(r.data)).catch((e) => setErr(e.status === 401 ? 'login' : e.message)); }, []);

  if (err === 'login') return <p>Please <a href={LOGIN_URL}>log in</a> to see your library.</p>;
  if (err) return <p className="err">{err}</p>;
  if (!books) return <p className="muted">Loading…</p>;

  return (
    <>
      <span className="eyebrow">Your books</span>
      <h1>My library</h1>
      {books.length ? (
        <div className="shelf">{books.map((b, i) => <BookCard key={b.slug} b={b} i={i} />)}</div>
      ) : (
        <p className="muted">Books you read or unlock appear here. <Link to="/">Browse the library</Link>.</p>
      )}
    </>
  );
}
