const BASE = import.meta.env.VITE_API_URL || '';
export const TOKEN_KEY = import.meta.env.VITE_TOKEN_KEY || 'token';
export const LOGIN_URL = import.meta.env.VITE_LOGIN_URL || '/login';
export const SIMULATE = import.meta.env.VITE_SIMULATE_PAYMENTS === 'true';

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
