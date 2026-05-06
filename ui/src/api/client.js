const DEFAULT_HEADERS = {
  Accept: 'application/json',
  'X-Requested-With': 'XMLHttpRequest',
};

const ACCESS_TOKEN_KEY = 'auth_access_token';
const REFRESH_TOKEN_KEY = 'auth_refresh_token';
const TOKEN_TYPE_KEY = 'auth_token_type';

function normalizeToken(value) {
  return typeof value === 'string' ? value.trim() : '';
}

export function getAccessToken() {
  return window.localStorage.getItem(ACCESS_TOKEN_KEY) || '';
}

export function getRefreshToken() {
  return window.localStorage.getItem(REFRESH_TOKEN_KEY) || '';
}

export function getTokenType() {
  return window.localStorage.getItem(TOKEN_TYPE_KEY) || '';
}

export function setAccessToken(token) {
  const normalizedToken = normalizeToken(token);

  if (normalizedToken) {
    window.localStorage.setItem(ACCESS_TOKEN_KEY, normalizedToken);
    return;
  }

  window.localStorage.removeItem(ACCESS_TOKEN_KEY);
}

export function setAuthTokens(tokens = {}) {
  setAccessToken(tokens.access_token || tokens.accessToken || tokens.token || '');

  const refreshToken = normalizeToken(
    tokens.refresh_token || tokens.refreshToken || ''
  );

  if (refreshToken) {
    window.localStorage.setItem(REFRESH_TOKEN_KEY, refreshToken);
  } else {
    window.localStorage.removeItem(REFRESH_TOKEN_KEY);
  }

  const tokenType = normalizeToken(tokens.token_type || tokens.tokenType || '');

  if (tokenType) {
    window.localStorage.setItem(TOKEN_TYPE_KEY, tokenType);
  } else {
    window.localStorage.removeItem(TOKEN_TYPE_KEY);
  }
}

export function clearAccessToken() {
  window.localStorage.removeItem(ACCESS_TOKEN_KEY);
}

export function clearAuthTokens() {
  clearAccessToken();
  window.localStorage.removeItem(REFRESH_TOKEN_KEY);
  window.localStorage.removeItem(TOKEN_TYPE_KEY);
}

async function parseResponseBody(response) {
  const contentType = response.headers.get('content-type') || '';

  if (contentType.includes('application/json')) {
    return response.json();
  }

  const text = await response.text();

  if (!text) {
    return null;
  }

  try {
    return JSON.parse(text);
  } catch {
    return { message: text };
  }
}

function createHttpError(response, payload) {
  const error = new Error(
    payload?.message || `The request failed with status ${response.status}.`
  );

  error.status = response.status;
  error.errors = payload?.errors || {};
  error.payload = payload;

  return error;
}

export async function request(path, options = {}) {
  const { body, method = 'GET' } = options;
  const headers = { ...DEFAULT_HEADERS };
  const accessToken = getAccessToken();

  if (body !== undefined) {
    headers['Content-Type'] = 'application/json';
  }

  if (accessToken) {
    headers.Authorization = `Bearer ${accessToken}`;
  }

  const response = await fetch(path, {
    body: body === undefined ? undefined : JSON.stringify(body),
    headers,
    method,
  });

  const payload = await parseResponseBody(response);

  if (!response.ok) {
    if (response.status === 401) {
      const latestAccessToken = getAccessToken();
      const shouldClearAuthTokens =
        (!accessToken && !latestAccessToken) ||
        (Boolean(accessToken) && latestAccessToken === accessToken);

      if (shouldClearAuthTokens) {
        clearAuthTokens();
      }
    }

    throw createHttpError(response, payload);
  }

  return payload;
}
