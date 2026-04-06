import { request } from './client';

export async function getAuthenticated() {
  const payload = await request('/auth/me');

  return payload?.data || null;
}

export async function sign(credentials) {
  const payload = await request('/auth/login', {
    body: credentials,
    csrf: true,
    method: 'POST',
  });

  return payload?.data || null;
}

export async function sgnup(accountData) {
  const payload = await request('/auth/signup', {
    body: accountData,
    csrf: true,
    method: 'POST',
  });

  return payload?.data || null;
}

export async function signout() {
  const payload = await request('/auth/logout', {
    csrf: true,
    method: 'POST',
  });

  return payload?.message || '';
}
