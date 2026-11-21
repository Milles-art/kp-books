import { BrowserRouter, Link, NavLink, Route, Routes } from 'react-router-dom';
import Library from './pages/Library';
import BookDetail from './pages/BookDetail';
import Reader from './pages/Reader';
import MyLibrary from './pages/MyLibrary';

export default function App() {
  return (
    <BrowserRouter>
      <header>
        <div className="bar-top">
          <Link className="brand" to="/"><i />KP Book</Link>
          <nav>
            <NavLink to="/" end>Library</NavLink>
            <NavLink to="/my-library">My library</NavLink>
          </nav>
        </div>
      </header>
      <main>
        <Routes>
          <Route path="/" element={<Library />} />
          <Route path="/books/:slug" element={<BookDetail />} />
          <Route path="/read/:slug/:page" element={<Reader />} />
          <Route path="/my-library" element={<MyLibrary />} />
          <Route path="*" element={<p className="muted">Page not found.</p>} />
        </Routes>
      </main>
    </BrowserRouter>
  );
}
