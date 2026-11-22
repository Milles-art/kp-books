import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api, Spinner } from '../lib/api';

const initials = (name) => name.split(/\s+/).filter(Boolean).slice(0, 2).map((w) => w[0]).join('').toUpperCase();

export default function Authors() {
  const [authors, setAuthors] = useState(null);
  const [err, setErr] = useState('');

  useEffect(() => { api('/authors').then((r) => setAuthors(r.data)).catch((e) => setErr(e.message)); }, []);

  if (err) return <p className="err">{err}</p>;
  if (!authors) return <Spinner />;

  return (
    <>
      <div className="page-head">
        <span className="eyebrow">THE PEOPLE BEHIND THE BOOKS</span>
        <h1>Authors</h1>
      </div>
      {authors.length ? (
        <div className="authors-grid">
          {authors.map((a) => (
            <Link className="author-card" key={a.slug} to={`/authors/${a.slug}`}>
              {a.portrait_url
                ? <img className="author-portrait" src={a.portrait_url} alt={a.name} />
                : <span className="author-portrait author-initials" aria-hidden="true">{initials(a.name)}</span>}
              <h3>{a.name}</h3>
              <div className="author-meta">
                {a.role && <span>{a.role}</span>}
                {a.location && <span>{a.location}</span>}
                <span>{a.books_count} {a.books_count === 1 ? 'book' : 'books'}</span>
              </div>
            </Link>
          ))}
        </div>
      ) : (
        <p className="muted">No authors listed yet.</p>
      )}
    </>
  );
}
