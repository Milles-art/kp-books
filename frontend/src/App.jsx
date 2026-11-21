import { useEffect, useState } from 'react';
import { BrowserRouter, Link, NavLink, Route, Routes } from 'react-router-dom';
import Library from './pages/Library';
import BookDetail from './pages/BookDetail';
import Reader from './pages/Reader';
import MyLibrary from './pages/MyLibrary';
import Footer from './components/Footer';
import { Icon } from './components/Icon';

const WEAR = import.meta.env.VITE_WEAR_URL || '/';
const Page = ({ children }) => <div className="wrap page">{children}</div>;

function useTheme() {
  const [theme, setTheme] = useState(() => {
    try { return localStorage.getItem('kpbook.theme') || 'light'; } catch { return 'light'; }
  });
  useEffect(() => {
    document.documentElement.dataset.theme = theme;
    try { localStorage.setItem('kpbook.theme', theme); } catch { /* ignore */ }
  }, [theme]);
  return [theme, () => setTheme((t) => (t === 'dark' ? 'light' : 'dark'))];
}

export default function App() {
  const [, toggle] = useTheme();
  return (
    <BrowserRouter>
      <header className="top">
        <div className="top-in">
          <Link className="logo" to="/">KP BOOK</Link>
          <nav className="menu">
            <NavLink to="/" end>Library</NavLink>
            <NavLink to="/my-library">My library</NavLink>
            <a href={WEAR}>KP Wear</a>
          </nav>
          <div className="icons">
            <Link to="/my-library" aria-label="My library"><Icon n="user" /></Link>
            <button onClick={toggle} aria-label="Switch light or dark"><Icon n="sun" /></button>
          </div>
        </div>
      </header>
      <main>
        <Routes>
          <Route path="/" element={<Library />} />
          <Route path="/books/:slug" element={<Page><BookDetail /></Page>} />
          <Route path="/read/:slug/:page" element={<Page><Reader /></Page>} />
          <Route path="/my-library" element={<Page><MyLibrary /></Page>} />
          <Route path="*" element={<Page><p className="muted">Page not found.</p></Page>} />
        </Routes>
      </main>
      <Footer />
    </BrowserRouter>
  );
}
