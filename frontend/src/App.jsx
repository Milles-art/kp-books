import { BrowserRouter, Route, Routes } from 'react-router-dom';
import { Component, useEffect, useState } from 'react';
import Library from './pages/Library';
import BookDetail from './pages/BookDetail';
import Reader from './pages/Reader';
import MyLibrary from './pages/MyLibrary';
import Saved from './pages/Saved';
import Authors from './pages/Authors';
import AuthorDetail from './pages/AuthorDetail';
import Devices from './pages/Devices';
import { Header, Footer } from '@Milles-art/kp-shared';

class ErrorBoundary extends Component {
  constructor(props) { super(props); this.state = { err: null }; }
  static getDerivedStateFromError(err) { return { err }; }
  render() {
    if (this.state.err) {
      return <pre style={{ color: 'red', padding: 20, whiteSpace: 'pre-wrap', fontFamily: 'monospace' }}>{String(this.state.err && this.state.err.stack || this.state.err)}</pre>;
    }
    return this.props.children;
  }
}

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
  const payments = [
    { src: '/payments/mpesa.png', alt: 'M-Pesa' },
    { src: '/payments/airtel-money.png', alt: 'Airtel Money' },
    { src: '/payments/halopesa.png', alt: 'HaloPesa' },
    { src: '/payments/yas.png', alt: 'Mixx by Yas' },
    { src: '/payments/selcom.png', alt: 'Selcom' },
    { src: '/payments/visa.png', alt: 'Visa' },
    { src: '/payments/mastercard.png', alt: 'Mastercard' },
  ];
  return (
    <ErrorBoundary>
      <BrowserRouter>
        <Header
          wearUrl={WEAR}
          onToggleTheme={toggle}
          nav={[
            { label: 'Books', to: '/' },
            { label: 'Authors', to: '/authors' },
            { label: 'Saved', to: '/saved' },
            { label: 'My library', to: '/my-library' },
            { label: 'Devices', to: '/devices' },
          ]}
        />
        <main>
          <Routes>
            <Route path="/" element={<Page><Library /></Page>} />
            <Route path="/books/:slug" element={<Page><BookDetail /></Page>} />
            <Route path="/read/:slug/:page" element={<Page><Reader /></Page>} />
            <Route path="/my-library" element={<Page><MyLibrary /></Page>} />
            <Route path="/saved" element={<Page><Saved /></Page>} />
            <Route path="/authors" element={<Page><Authors /></Page>} />
            <Route path="/authors/:slug" element={<Page><AuthorDetail /></Page>} />
            <Route path="/devices" element={<Page><Devices /></Page>} />
            <Route path="*" element={<Page><p className="muted">Page not found.</p></Page>} />
          </Routes>
        </main>
        <Footer wearUrl={WEAR} payments={payments} />
      </BrowserRouter>
    </ErrorBoundary>
  );
}