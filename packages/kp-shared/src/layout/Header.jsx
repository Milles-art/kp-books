import { Link, NavLink } from 'react-router-dom';
import { Icon } from '../ui/Icon';

export default function Header({
  brand = 'KP BOOK',
  wearUrl = '/',
  nav = [{ label: 'Library', to: '/' }, { label: 'My library', to: '/my-library' }],
  onToggleTheme,
  libraryTo = '/my-library',
}) {
  return (
    <header className="top">
      <div className="top-in">
        <Link className="logo" to="/">{brand}</Link>
        <nav className="menu">
          {nav.map((n) => (
            n.to ? <NavLink key={n.label} to={n.to} end={n.end}>{n.label}</NavLink> : <a key={n.label} href={n.href}>{n.label}</a>
          ))}
          <a href={wearUrl}>KP Wear</a>
        </nav>
        <div className="icons">
          <Link to={libraryTo} aria-label="My library"><Icon n="user" /></Link>
          {onToggleTheme && <button onClick={onToggleTheme} aria-label="Switch light or dark"><Icon n="sun" /></button>}
        </div>
      </div>
    </header>
  );
}