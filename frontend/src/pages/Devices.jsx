import { useCallback, useEffect, useState } from 'react';
import { api, Spinner } from '../lib/api';

const short = (id) => (id && id.length > 18 ? `${id.slice(0, 10)}…${id.slice(-6)}` : id);

export default function Devices() {
  const [data, setData] = useState(null);
  const [err, setErr] = useState('');
  const [busy, setBusy] = useState(false);

  const load = useCallback(() => api('/books/devices')
    .then((r) => setData(r.data))
    .catch((e) => setErr(e.message)), []);

  useEffect(() => { load(); }, [load]);

  const revoke = (id) => {
    if (busy) return;
    setBusy(true);
    setErr('');
    api(`/books/devices/${id}`, { method: 'DELETE' })
      .then(load)
      .catch((e) => setErr(e.message))
      .finally(() => setBusy(false));
  };

  if (err) return <p className="err">{err}</p>;
  if (!data) return <Spinner />;

  const devices = data.devices || [];
  return (
    <>
      <div className="page-head">
        <span className="eyebrow">SECURITY</span>
        <h1>Your reading devices</h1>
        <p className="muted">You can read on up to {data.max_devices} devices at once. This is a soft limit — if you sign in somewhere new, the oldest device is signed out first.</p>
      </div>

      <div className="devices">
        {devices.length ? devices.map((d) => (
          <div className="device-row" key={d.id}>
            <div>
              <b>{d.name || short(d.device_id)}</b>
              {d.name && <div className="device-id muted small">{short(d.device_id)}</div>}
              <div className="muted small">Last seen {d.last_seen ? new Date(d.last_seen).toLocaleString() : 'never'}</div>
            </div>
            <span className={`status-pill ${d.active ? 'on' : 'off'}`}>{d.active ? 'Active' : 'Revoked'}</span>
            {d.active && (
              <button className="btn ghost sm" type="button" disabled={busy} onClick={() => revoke(d.id)}>Revoke</button>
            )}
          </div>
        )) : (
          <p className="muted">No devices have signed in to your account yet.</p>
        )}
      </div>

      <p className="muted small">Revoking a device signs it out straight away. You can sign back in later as long as you stay within the {data.max_devices}-device limit.</p>
    </>
  );
}
