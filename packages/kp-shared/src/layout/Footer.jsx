import { Link } from 'react-router-dom';
import { Icon } from '../ui/Icon';

const DEFAULT_TRUST = [
  ['book', 'Read anywhere', 'On your phone or computer, signed in to your account.'],
  ['shield', 'Secure payment', 'M-Pesa, Airtel Money, HaloPesa, Mixx by Yas and cards.'],
  ['coin', 'Pay what you can', 'From 1,000 TZS, and top up any time.'],
  ['chat', 'Real people', 'Chat with the KP team on WhatsApp, Mon to Sat.'],
];
const DEFAULT_ACCEPT_TEXT = ['M-PESA', 'airtel money', 'HaloPesa', 'Mixx by Yas', 'Selcom', 'VISA', 'Mastercard'];

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

function normalizePayment(p) {
  if (typeof p === 'string') return { type: 'text', label: p };
  if (p && p.src) return { type: 'img', src: p.src, alt: p.alt || p.label || '' };
  return { type: 'text', label: p.label || String(p) };
}

export default function Footer({
  brand = 'KP BOOK',
  giant = 'KP BOOK',
  wearUrl = '/',
  columns = null,
  trust = DEFAULT_TRUST,
  showTrust = true,
  payments = null,
  accept = null,
  socials = [],
  about = 'Stories made for everyday readers. Born in Dar es Salaam, read worldwide.',
  copyright = '© 2026 KP Wear. All rights reserved.',
  tagline = 'Art. People. Books. A bigger story.',
  footerBg = null,
  footerClass = '',
}) {
  const cols = (columns || DEFAULT_COLUMNS).map((c) => ({
    ...c,
    links: c.links.map((l) => (l.external && (l.href === null || l.href === undefined) ? { ...l, href: wearUrl } : l)),
  }));

  const payList = payments
    ? payments.map(normalizePayment)
    : (accept || DEFAULT_ACCEPT_TEXT).map((a) => ({ type: 'text', label: a }));

  return (
    <>
      {showTrust && trust && trust.length > 0 && (
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
      )}
      <footer className={footerClass} style={footerBg ? { background: footerBg } : undefined}>
        <div className="wrap">
          <div className="f-grid">
            <div>
              <span className="logo static">{brand}</span>
              <p className="f-about">{about}</p>
              {socials.length > 0 && (
                <div className="mt-6 flex gap-3">
                  {socials.map((s) => (
                    <a key={s.label} href={s.href} target="_blank" rel="noopener noreferrer" aria-label={s.label}
                       className="flex size-11 cursor-pointer items-center justify-center rounded-full bg-white/10 transition-colors hover:bg-white/20">
                      <Icon n={s.icon} size={20} />
                    </a>
                  ))}
                </div>
              )}
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
          <div className="accept">
            <b>WE ACCEPT</b>
            {payList.map((p, i) => (
              p.type === 'img'
                ? <span key={i} style={{ display: 'inline-flex', alignItems: 'center', justifyContent: 'center', height: 24, width: 40, padding: 4, borderRadius: 6, background: '#fff' }}>
                    <img src={p.src} alt={p.alt} style={{ maxHeight: '100%', maxWidth: '100%', objectFit: 'contain' }} loading="lazy" />
                  </span>
                : <span key={i}>{p.label}</span>
            ))}
          </div>
          <div className="giant" aria-hidden="true">{giant}</div>
          <div className="copy"><span>{copyright}</span><span>{tagline}</span></div>
        </div>
      </footer>
    </>
  );
}