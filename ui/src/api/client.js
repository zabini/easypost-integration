const DEFAULT_HEADERS = {
  Accept: 'application/json',
  'X-Requested-With': 'XMLHttpRequest',
};

const ACCESS_TOKEN_KEY = 'auth_access_token';

export function getAccessToken() {
  return window.localStorage.getItem(ACCESS_TOKEN_KEY) || '';
}

export function setAccessToken(token) {
  if (!token) {
    return;
  }

  window.localStorage.setItem(ACCESS_TOKEN_KEY, token);
}

export function clearAccessToken() {
  window.localStorage.removeItem(ACCESS_TOKEN_KEY);
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
      clearAccessToken();
    }

    throw createHttpError(response, payload);
  }

  return payload;
}
