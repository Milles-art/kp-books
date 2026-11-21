import { Link } from 'react-router-dom';
import { Icon } from '../ui/Icon';

const DEFAULT_TRUST = [
  ['book', 'Read anywhere', 'On your phone or computer, signed in to your account.'],
  ['shield', 'Secure payment', 'M-Pesa, Airtel Money, HaloPesa, Mixx by Yas and cards.'],
  ['coin', 'Pay what you can', 'From 1,000 TZS, and top up any time.'],
  ['chat', 'Real people', 'Chat with the KP team on WhatsApp, Mon to Sat.'],
];
const DEFAULT_ACCEPT = ['M-PESA', 'airtel money', 'HaloPesa', 'Mixx by Yas', 'Selcom', 'VISA', 'Mastercard'];

const DEFAULT_COLUMNS = [
  { title: 'Books', links: [{ label: 'All books', to: '/' }, { label: 'My library', to: '/my-library' }] },
  { title: 'KP Wear', links: [{ label: 'Visit the shop', href: null, external: true }] },
  { title: 'Get in touch', links: [
    { label: 'hello@kpwear.co.tz', href: 'mailto:hello@kpwear.co.tz' },
    { label: '0757 829 130', href: 'tel:+255757829130' },
    { label: '0796 829 131', href: 'tel:+255796829131' },
    { label: 'Dar es Salaam, Tanzania', href: null },
  ] },
];

export default function Footer({
  brand = 'KP BOOK',
  giant = 'KP BOOK',
  wearUrl = '/',
  columns = null,
  trust = DEFAULT_TRUST,
  accept = DEFAULT_ACCEPT,
  copyright = '© 2026 KP Wear. All rights reserved.',
  tagline = 'Art. People. Books. A bigger story.',
  about = 'Stories made for everyday readers. Born in Dar es Salaam, read worldwide.',
}) {
  const cols = (columns || DEFAULT_COLUMNS).map((c) => ({
    ...c,
    links: c.links.map((l) => (l.external && l.href === null ? { ...l, href: wearUrl } : l)),
  }));

  return (
    <>
      <section className="trust">
        <div className="wrap trust-in">
          {trust.map(([n, t, d]) => (
            <div className="ti" key={t}>
              <span className="tile"><Icon n={n} /></span>
              <div><b>{t}</b><p>{d}</p></div>
            </div>
          ))}
        </div>
      </section>
      <footer>
        <div className="wrap">
          <div className="f-grid">
            <div>
              <span className="logo static">{brand}</span>
              <p className="f-about">{about}</p>
            </div>
            {cols.map((c) => (
              <div key={c.title}>
                <h4>{c.title}</h4>
                {c.links.map((l) => (
                  l.to ? <Link key={l.label} to={l.to}>{l.label}</Link>
                    : <a key={l.label} href={l.href}>{l.label}</a>
                ))}
              </div>
            ))}
          </div>
          <div className="accept"><b>WE ACCEPT</b>{accept.map((a) => <span key={a}>{a}</span>)}</div>
          <div className="giant" aria-hidden="true">{giant}</div>
          <div className="copy"><span>{copyright}</span><span>{tagline}</span></div>
        </div>
      </footer>
    </>
  );
}