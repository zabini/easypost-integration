import { clearAuthTokens, request, setAuthTokens } from './client';

function resolveTokens(payload) {
  const meta = payload?.meta || {};
  const metaTokens = meta?.tokens || {};
  const payloadTokens = payload?.tokens || {};

  return {
    access_token:
      meta.access_token ||
      meta.accessToken ||
      metaTokens.access_token ||
      metaTokens.accessToken ||
      payload.access_token ||
      payload.accessToken ||
      payloadTokens.access_token ||
      payloadTokens.accessToken ||
      payloadTokens.token ||
      payload.token ||
      '',
    refresh_token:
      meta.refresh_token ||
      meta.refreshToken ||
      metaTokens.refresh_token ||
      metaTokens.refreshToken ||
      payload.refresh_token ||
      payload.refreshToken ||
      payloadTokens.refresh_token ||
      payloadTokens.refreshToken ||
      '',
    token_type:
      meta.token_type ||
      meta.tokenType ||
      payload.token_type ||
      payload.tokenType ||
      '',
  };
}

export async function getAuthenticated() {
  const payload = await request('/auth/me');

  return payload?.data || null;
}

export async function sign(credentials) {
  const payload = await request('/auth/login', {
    body: credentials,
    method: 'POST',
  });

  setAuthTokens(resolveTokens(payload || {}));

  return payload?.data || null;
}

export async function signup(accountData) {
  const payload = await request('/auth/signup', {
    body: accountData,
    method: 'POST',
  });

  setAuthTokens(resolveTokens(payload || {}));

  return payload?.data || null;
}

export const sgnup = signup;

export async function signout() {
  const payload = await request('/auth/logout', { method: 'POST' });

  clearAuthTokens();

  return payload?.message || '';
}
