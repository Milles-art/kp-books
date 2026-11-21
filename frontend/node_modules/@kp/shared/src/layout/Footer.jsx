import { Link } from 'react-router-dom';
import { Icon } from '../ui/Icon';

const WEAR = import.meta.env.VITE_WEAR_URL || '/';
const TRUST = [
  ['book', 'Read anywhere', 'On your phone or computer, signed in to your account.'],
  ['shield', 'Secure payment', 'M-Pesa, Airtel Money, HaloPesa, Mixx by Yas and cards.'],
  ['coin', 'Pay what you can', 'From 1,000 TZS, and top up any time.'],
  ['chat', 'Real people', 'Chat with the KP team on WhatsApp, Mon to Sat.'],
];
const ACCEPT = ['M-PESA', 'airtel money', 'HaloPesa', 'Mixx by Yas', 'Selcom', 'VISA', 'Mastercard'];

export default function Footer() {
  return (
    <>
      <section className="trust">
        <div className="wrap trust-in">
          {TRUST.map(([n, t, d]) => (
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
              <span className="logo static">KP BOOK</span>
              <p className="f-about">Stories made for everyday readers. Born in Dar es Salaam, read worldwide.</p>
            </div>
            <div><h4>Books</h4><Link to="/">All books</Link><Link to="/my-library">My library</Link></div>
            <div><h4>KP Wear</h4><a href={WEAR}>Visit the shop</a></div>
            <div>
              <h4>Get in touch</h4>
              <a href="mailto:hello@kpwear.co.tz">hello@kpwear.co.tz</a>
              <a href="tel:+255757829130">0757 829 130</a>
              <a href="tel:+255796829131">0796 829 131</a>
              <span>Dar es Salaam, Tanzania</span>
            </div>
          </div>
          <div className="accept"><b>WE ACCEPT</b>{ACCEPT.map((a) => <span key={a}>{a}</span>)}</div>
          <div className="giant" aria-hidden="true">KP BOOK</div>
          <div className="copy"><span>© 2026 KP Wear. All rights reserved.</span><span>Art. People. Books. A bigger story.</span></div>
        </div>
      </footer>
    </>
  );
}
