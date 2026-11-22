const BASE = import.meta.env.VITE_API_URL || '';
export const TOKEN_KEY = import.meta.env.VITE_TOKEN_KEY || 'token';
export const LOGIN_URL = import.meta.env.VITE_LOGIN_URL || '/login';
export const SIMULATE = import.meta.env.VITE_SIMULATE_PAYMENTS === 'true';
const DEVICE_KEY = 'kp.device';

/** Stable, anonymous per-browser identifier used for soft device limits. */
export function getDeviceId() {
  try {
    let id = localStorage.getItem(DEVICE_KEY);
    if (!id) {
      id = typeof crypto !== 'undefined' && crypto.randomUUID
        ? crypto.randomUUID()
        : `dev-${Date.now()}-${Math.random().toString(36).slice(2)}`;
      localStorage.setItem(DEVICE_KEY, id);
    }
    return id;
  } catch {
    return 'unknown';
  }
}

export function getToken() {
  try {
    return localStorage.getItem(TOKEN_KEY) || import.meta.env.VITE_DEV_TOKEN || null;
  } catch {
    return import.meta.env.VITE_DEV_TOKEN || null;
  }
}

/** Calls the Laravel API. Errors carry .status and .data (for example code PAGE_LOCKED). */
export async function api(path, { method = 'GET', body } = {}) {
  const token = getToken();
  const res = await fetch(`${BASE}/api/v1${path}`, {
    method,
    headers: {
      Accept: 'application/json',
      'X-Device-Id': getDeviceId(),
      ...(body ? { 'Content-Type': 'application/json' } : {}),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body: body ? JSON.stringify(body) : undefined,
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw Object.assign(new Error(data.message || 'Something went wrong. Please try again.'), { status: res.status, data });
  return data;
}

export const money = (n) => `${Math.round(n).toLocaleString('en-US')} TZS`;
