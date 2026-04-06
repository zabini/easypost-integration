const DEFAULT_HEADERS = {
  Accept: 'application/json',
  'X-Requested-With': 'XMLHttpRequest',
};

function readCookie(name) {
  const cookies = document.cookie ? document.cookie.split('; ') : [];
  const entry = cookies.find((cookie) => cookie.startsWith(`${name}=`));

  if (!entry) {
    return '';
  }

  return entry.slice(name.length + 1);
}

function getXsrfToken() {
  const token = readCookie('XSRF-TOKEN');

  return token ? decodeURIComponent(token) : '';
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

async function ensureCsrfCookie() {
  const response = await fetch('/sanctum/csrf-cookie', {
    credentials: 'include',
    headers: DEFAULT_HEADERS,
    method: 'GET',
  });

  if (!response.ok) {
    const payload = await parseResponseBody(response);

    throw createHttpError(response, payload);
  }
}

export async function request(path, options = {}) {
  const { body, csrf = false, method = 'GET' } = options;

  if (csrf) {
    await ensureCsrfCookie();
  }

  const headers = { ...DEFAULT_HEADERS };

  if (body !== undefined) {
    headers['Content-Type'] = 'application/json';
  }

  const xsrfToken = csrf ? getXsrfToken() : '';

  if (xsrfToken) {
    headers['X-XSRF-TOKEN'] = xsrfToken;
  }

  const response = await fetch(path, {
    body: body === undefined ? undefined : JSON.stringify(body),
    credentials: 'include',
    headers,
    method,
  });

  const payload = await parseResponseBody(response);

  if (!response.ok) {
    throw createHttpError(response, payload);
  }

  return payload;
}
