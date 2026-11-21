import { BrowserRouter, Route, Routes } from 'react-router-dom';
import { useEffect, useState } from 'react';
import Library from './pages/Library';
import BookDetail from './pages/BookDetail';
import Reader from './pages/Reader';
import MyLibrary from './pages/MyLibrary';
import { Header, Footer } from '@kp/shared';

const WEAR = import.meta.env.VITE_WEAR_URL || '/';

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

const Page = ({ children }) => <div className="wrap page">{children}</div>;

export default function App() {
  const [, toggle] = useTheme();
  return (
    <BrowserRouter>
      <Header wearUrl={WEAR} onToggleTheme={toggle} />
      <main>
        <Routes>
          <Route path="/" element={<Page><Library /></Page>} />
          <Route path="/books/:slug" element={<Page><BookDetail /></Page>} />
          <Route path="/read/:slug/:page" element={<Page><Reader /></Page>} />
          <Route path="/my-library" element={<Page><MyLibrary /></Page>} />
          <Route path="*" element={<Page><p className="muted">Page not found.</p></Page>} />
        </Routes>
      </main>
      <Footer wearUrl={WEAR} />
    </BrowserRouter>
  );
}