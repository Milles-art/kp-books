import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api, LOGIN_URL, Spinner } from '../lib/api';
import { BookCard } from './Library';

export default function Saved() {
  const [data, setData] = useState({ saved: [], collections: [] });
  const [err, setErr] = useState('');
  const [loading, setLoading] = useState(true);
  const [newName, setNewName] = useState('');
  const [busy, setBusy] = useState(false);
  const [editing, setEditing] = useState(null);
  const [rename, setRename] = useState('');
  const [queries, setQueries] = useState({});

  const load = useCallback(() => {
    setLoading(true);
    return api('/books/saved')
      .then((r) => { setData(r.data); setErr(''); })
      .catch((e) => setErr(e.status === 401 ? 'login' : e.message))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => { load(); }, [load]);

  // Every write goes through here: block double submits, reload, surface errors.
  const run = (action) => {
    if (busy) return;
    setBusy(true);
    setErr('');
    Promise.resolve()
      .then(action)
      .then(load)
      .catch((e) => setErr(e.status === 401 ? 'login' : e.message))
      .finally(() => setBusy(false));
  };

  const setQuery = (id, value) => setQueries((m) => ({ ...m, [id]: value }));

  if (err === 'login') {
    return <p className="center"><a href={LOGIN_URL}>Sign in with KP Wear to see your saved books.</a></p>;
  }
  if (loading && !data.saved.length && !data.collections.length) return <Spinner />;

  const saved = data.saved || [];
  const collections = data.collections || [];

  return (
    <>
      <div className="page-head">
        <span className="eyebrow">YOUR SHELF</span>
        <h1>Saved &amp; collections</h1>
        <p className="muted">Books you want to come back to, grouped your way. <Link to="/">Save a book</Link> from its page to see it here.</p>
      </div>
      {err && <p className="err">{err}</p>}

      <section className="sec-head">
        <div><span className="eyebrow">Section 01 / Bookmarks</span><h2>Saved books</h2></div>
      </section>
      {saved.length ? (
        <div className="shelf">{saved.map((b, i) => <BookCard key={b.slug} b={b} i={i} />)}</div>
      ) : (
        <p className="muted">You haven't saved any books yet. <Link to="/">Browse the library</Link> and save one to start your shelf.</p>
      )}

      <section className="sec-head">
        <div><span className="eyebrow">Section 02 / Grouped</span><h2>Collections</h2></div>
      </section>
      <form className="add-book-row" onSubmit={(e) => {
        e.preventDefault();
        if (!newName.trim()) return;
        run(() => api('/books/collections', { method: 'POST', body: { name: newName.trim() } }).then(() => setNewName('')));
      }}>
        <input className="search" type="text" placeholder="New collection name" value={newName} onChange={(e) => setNewName(e.target.value)} aria-label="New collection name" />
        <button className="btn green sm" type="submit" disabled={busy || !newName.trim()}>New collection</button>
      </form>

      {collections.length ? collections.map((c) => {
        const q = (queries[c.id] || '').trim().toLowerCase();
        const results = saved.filter((b) => (
          !(c.books || []).some((x) => x.slug === b.slug)
          && `${b.title} ${b.subtitle || ''}`.toLowerCase().includes(q)
        ));
        return (
          <div className="collection" key={c.id}>
            <div className="collection-head">
              {editing === c.id ? (
                <form className="add-book-row" onSubmit={(e) => {
                  e.preventDefault();
                  if (!rename.trim()) return;
                  run(() => api(`/books/collections/${c.id}`, { method: 'PATCH', body: { name: rename.trim() } }).then(() => setEditing(null)));
                }}>
                  <input className="search" type="text" value={rename} onChange={(e) => setRename(e.target.value)} aria-label={`Rename ${c.name}`} autoFocus />
                  <button className="btn green sm" type="submit" disabled={busy || !rename.trim()}>Save</button>
                  <button className="btn ghost sm" type="button" onClick={() => setEditing(null)}>Cancel</button>
                </form>
              ) : (
                <h3>{c.name} <span className="muted small">({c.count ?? (c.books || []).length})</span></h3>
              )}
              <div className="collection-actions">
                <button className="btn ghost sm" type="button" disabled={busy || editing === c.id} onClick={() => { setEditing(c.id); setRename(c.name); }}>Rename</button>
                <button className="btn ghost sm" type="button" disabled={busy} onClick={() => run(() => api(`/books/collections/${c.id}`, { method: 'DELETE' }))}>Delete</button>
              </div>
            </div>

            {(c.books || []).length ? (
              <div className="shelf">
                {c.books.map((b, i) => (
                  <div className="coll-book" key={b.slug}>
                    <BookCard b={b} i={i} />
                    <button className="btn ghost sm" type="button" disabled={busy} onClick={() => run(() => api(`/books/collections/${c.id}/books/${b.slug}`, { method: 'DELETE' }))}>Remove from collection</button>
                  </div>
                ))}
              </div>
            ) : (
              <p className="muted small">Nothing in this collection yet.</p>
            )}

            <div className="add-book-row">
              <input className="search" type="search" placeholder="Add a book from your saved books" value={queries[c.id] || ''} onChange={(e) => setQuery(c.id, e.target.value)} aria-label={`Find a book to add to ${c.name}`} />
              {q && (
                <div className="add-book-row">
                  {results.length ? results.map((b) => (
                    <div className="add-book-row" key={b.slug}>
                      <span className="small">{b.title}</span>
                      <button className="btn green sm" type="button" disabled={busy} onClick={() => run(() => api(`/books/collections/${c.id}/books/${b.slug}`, { method: 'POST' }).then(() => setQuery(c.id, '')))}>Add</button>
                    </div>
                  )) : <span className="muted small">No matching saved books.</span>}
                </div>
              )}
            </div>
          </div>
        );
      }) : (
        <p className="muted">No collections yet. Give one a name above to start grouping your saved books.</p>
      )}
    </>
  );
}
