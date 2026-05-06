import { clearAccessToken, request, setAccessToken } from './client';

export async function getAuthenticated() {
  const payload = await request('/auth/me');

  return payload?.data || null;
}

export async function sign(credentials) {
  const payload = await request('/auth/login', {
    body: credentials,
    method: 'POST',
  });

  setAccessToken(payload?.meta?.access_token || '');

  return payload?.data || null;
}

export async function sgnup(accountData) {
  const payload = await request('/auth/signup', {
    body: accountData,
    method: 'POST',
  });

  setAccessToken(payload?.meta?.access_token || '');

  return payload?.data || null;
}

export async function signout() {
  const payload = await request('/auth/logout', { method: 'POST' });

  clearAccessToken();

  return payload?.message || '';
}
