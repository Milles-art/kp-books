import { useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { api, Spinner } from '../lib/api';
import { BookCard } from './Library';

export default function AuthorDetail() {
  const { slug } = useParams();
  const [author, setAuthor] = useState(null);
  const [err, setErr] = useState('');

  const load = useCallback(() => api(`/authors/${slug}`)
    .then((r) => setAuthor(r.data))
    .catch((e) => setErr(e.status === 404 ? 'Author not found.' : e.message)), [slug]);

  useEffect(() => { load(); }, [load]);

  if (err) {
    return <><p><Link className="btn ghost sm" to="/authors">Back to authors</Link></p><p className="err">{err}</p></>;
  }
  if (!author) return <Spinner />;

  const bio = (author.bio || '').split(/\n\n+/).filter(Boolean);

  return (
    <>
      <p><Link className="btn ghost sm" to="/authors">Back to authors</Link></p>
      <div className="detail author-detail">
        {author.portrait_url
          ? <img className="author-portrait" src={author.portrait_url} alt={author.name} />
          : <span className="author-portrait author-initials" aria-hidden="true">{author.name.split(/\s+/).filter(Boolean).slice(0, 2).map((w) => w[0]).join('').toUpperCase()}</span>}
        <div>
          <span className="eyebrow">KP Book / Author</span>
          <h1>{author.name}</h1>
          <p className="muted">{author.role}{author.location ? ` · ${author.location}` : ''}</p>
          {bio.length ? bio.map((p, i) => <p className="lit" key={i}>{p}</p>) : <p className="muted">No biography yet.</p>}
        </div>
      </div>

      <section className="sec-head">
        <div><span className="eyebrow">Section 01 / The work</span><h2>BOOKS BY {author.name}</h2></div>
      </section>
      {(author.books || []).length ? (
        <div className="shelf">{author.books.map((b, i) => <BookCard key={b.slug} b={b} i={i} />)}</div>
      ) : (
        <p className="muted">No books by this author yet.</p>
      )}
    </>
  );
}
