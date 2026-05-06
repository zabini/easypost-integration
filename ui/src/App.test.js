import { StrictMode } from 'react';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import App from './App';

function createJsonResponse({ ok, payload, status }) {
  return {
    ok,
    status,
    headers: {
      get: () => 'application/json',
    },
    json: async () => payload,
    text: async () => JSON.stringify(payload),
  };
}

beforeEach(() => {
  window.localStorage.clear();
  global.fetch = jest.fn();
});

afterEach(() => {
  jest.restoreAllMocks();
});

test('renders sign in form for guest users', async () => {
  global.fetch.mockImplementation(async (path) => {
    if (path === '/auth/me') {
      return createJsonResponse({
        ok: false,
        payload: { message: 'Unauthenticated.' },
        status: 401,
      });
    }

    throw new Error(`Unexpected request: ${path}`);
  });

  render(<App />);

  expect(await screen.findByText(/access your account/i)).toBeInTheDocument();
  expect(screen.getByRole('button', { name: /^sign in$/i })).toBeInTheDocument();
  expect(screen.getByRole('tab', { name: /^sign in$/i })).toBeInTheDocument();
  expect(screen.getByRole('tab', { name: /^sign up$/i })).toBeInTheDocument();
});

test('renders shipping label workspace for authenticated users', async () => {
  window.localStorage.setItem('auth_access_token', 'test-token');
  const authHeadersByPath = {};

  global.fetch.mockImplementation(async (path, options = {}) => {
    authHeadersByPath[path] = options?.headers?.Authorization || '';

    if (path === '/auth/me') {
      return createJsonResponse({
        ok: true,
        payload: {
          data: {
            id: 7,
            name: 'Jane Doe',
            email: 'jane@example.com',
          },
        },
        status: 200,
      });
    }

    if (path === '/shipping-labels') {
      return createJsonResponse({
        ok: true,
        payload: {
          data: [
            {
              id: 17,
              tracking_code: '9400100000000000000000',
              label_url: 'https://example.test/label.pdf',
              carrier: 'USPS',
              service: 'Priority',
              rate_amount: '8.68',
              rate_currency: 'USD',
              status: 'purchased',
              from_address: {
                name: 'Jane Sender',
                street1: '417 Montgomery Street',
                city: 'San Francisco',
                state: 'CA',
                zip: '94104',
                country: 'US',
              },
              to_address: {
                name: 'John Receiver',
                street1: '388 Townsend St',
                city: 'San Francisco',
                state: 'CA',
                zip: '94107',
                country: 'US',
              },
              parcel: {
                weight_oz: 12,
                length_in: 10,
                width_in: 7,
                height_in: 4,
              },
              created_at: '2026-04-06T12:00:00Z',
            },
          ],
        },
        status: 200,
      });
    }

    throw new Error(`Unexpected request: ${path}`);
  });

  render(<App />);

  expect(await screen.findByText('9400100000000000000000')).toBeInTheDocument();
  expect(screen.getAllByText(/my shipments/i)).toHaveLength(2);
  expect(
    screen.getByRole('tab', { name: /^new shipment$/i })
  ).toBeInTheDocument();
  expect(screen.getByText(/get \/shipping-labels/i)).toBeInTheDocument();
  expect(authHeadersByPath['/auth/me']).toBe('Bearer test-token');
  expect(authHeadersByPath['/shipping-labels']).toBe('Bearer test-token');
});

test('redirects to list after sign in', async () => {
  global.fetch.mockImplementation(async (path) => {
    if (path === '/auth/login') {
      return createJsonResponse({
        ok: true,
        payload: {
          data: {
            id: 7,
            name: 'Jane Doe',
            email: 'jane@example.com',
          },
          meta: {
            access_token: 'new-token',
            token_type: 'Bearer',
          },
        },
        status: 200,
      });
    }

    if (path === '/shipping-labels') {
      return createJsonResponse({
        ok: true,
        payload: {
          data: [],
        },
        status: 200,
      });
    }

    throw new Error(`Unexpected request: ${path}`);
  });

  render(<App />);

  await screen.findByRole('button', { name: /^sign in$/i });

  fireEvent.change(screen.getByLabelText(/email/i), {
    target: { value: 'jane@example.com' },
  });
  fireEvent.change(screen.getByLabelText(/password/i), {
    target: { value: 'secret123' },
  });
  fireEvent.click(screen.getByRole('button', { name: /^sign in$/i }));

  expect(await screen.findByText(/jane doe/i)).toBeInTheDocument();
  expect(screen.getAllByText(/my shipments/i)).toHaveLength(2);
  expect(
    await screen.findByText(/no shipments found for your account/i)
  ).toBeInTheDocument();
});

test('keeps the latest token after sign in when a stale auth check returns 401', async () => {
  window.localStorage.setItem('auth_access_token', 'stale-token');
  const authHeadersByPath = {};
  let resolveAuthMeRequest;

  const staleAuthMeResponse = new Promise((resolve) => {
    resolveAuthMeRequest = resolve;
  });

  global.fetch.mockImplementation(async (path, options = {}) => {
    authHeadersByPath[path] = options?.headers?.Authorization || '';

    if (path === '/auth/me') {
      return staleAuthMeResponse;
    }

    if (path === '/auth/login') {
      return createJsonResponse({
        ok: true,
        payload: {
          data: {
            id: 7,
            name: 'Jane Doe',
            email: 'jane@example.com',
          },
          meta: {
            access_token: 'fresh-token',
            token_type: 'Bearer',
          },
        },
        status: 200,
      });
    }

    if (path === '/shipping-labels') {
      return createJsonResponse({
        ok: true,
        payload: {
          data: [],
        },
        status: 200,
      });
    }

    throw new Error(`Unexpected request: ${path}`);
  });

  render(<App />);

  await screen.findByRole('button', { name: /^sign in$/i });

  fireEvent.change(screen.getByLabelText(/email/i), {
    target: { value: 'jane@example.com' },
  });
  fireEvent.change(screen.getByLabelText(/password/i), {
    target: { value: 'secret123' },
  });
  fireEvent.click(screen.getByRole('button', { name: /^sign in$/i }));

  expect(await screen.findByText(/jane doe/i)).toBeInTheDocument();
  expect(window.localStorage.getItem('auth_access_token')).toBe('fresh-token');
  expect(authHeadersByPath['/shipping-labels']).toBe('Bearer fresh-token');

  resolveAuthMeRequest(
    createJsonResponse({
      ok: false,
      payload: { message: 'Unauthenticated.' },
      status: 401,
    })
  );

  await waitFor(() => {
    expect(window.localStorage.getItem('auth_access_token')).toBe('fresh-token');
    expect(screen.getByText(/jane doe/i)).toBeInTheDocument();
  });
});

test('stores returned tokens in localStorage after sign up', async () => {
  const authHeadersByPath = {};

  global.fetch.mockImplementation(async (path, options = {}) => {
    authHeadersByPath[path] = options?.headers?.Authorization || '';

    if (path === '/auth/signup') {
      return createJsonResponse({
        ok: true,
        payload: {
          data: {
            id: 9,
            name: 'John Doe',
            email: 'john@example.com',
          },
          meta: {
            access_token: 'signup-token',
            refresh_token: 'refresh-token',
            token_type: 'Bearer',
          },
        },
        status: 201,
      });
    }

    if (path === '/shipping-labels') {
      return createJsonResponse({
        ok: true,
        payload: {
          data: [],
        },
        status: 200,
      });
    }

    throw new Error(`Unexpected request: ${path}`);
  });

  render(<App />);

  fireEvent.click(screen.getByRole('tab', { name: /^sign up$/i }));

  fireEvent.change(screen.getByLabelText(/^name$/i), {
    target: { value: 'John Doe' },
  });
  fireEvent.change(screen.getByLabelText(/^email$/i), {
    target: { value: 'john@example.com' },
  });
  fireEvent.change(screen.getByLabelText(/^password$/i), {
    target: { value: 'secret123' },
  });
  fireEvent.change(screen.getByLabelText(/password confirmation/i), {
    target: { value: 'secret123' },
  });
  fireEvent.click(screen.getByRole('button', { name: /create account/i }));

  expect(await screen.findByText(/john doe/i)).toBeInTheDocument();
  expect(window.localStorage.getItem('auth_access_token')).toBe('signup-token');
  expect(window.localStorage.getItem('auth_refresh_token')).toBe('refresh-token');
  expect(window.localStorage.getItem('auth_token_type')).toBe('Bearer');
  expect(authHeadersByPath['/shipping-labels']).toBe('Bearer signup-token');
});

test('loads the authenticated session once in strict mode', async () => {
  window.localStorage.setItem('auth_access_token', 'test-token');

  let authMeRequests = 0;

  global.fetch.mockImplementation(async (path) => {
    if (path === '/auth/me') {
      authMeRequests += 1;

      return createJsonResponse({
        ok: true,
        payload: {
          data: {
            id: 7,
            name: 'Jane Doe',
            email: 'jane@example.com',
          },
        },
        status: 200,
      });
    }

    if (path === '/shipping-labels') {
      return createJsonResponse({
        ok: true,
        payload: {
          data: [],
        },
        status: 200,
      });
    }

    throw new Error(`Unexpected request: ${path}`);
  });

  render(
    <StrictMode>
      <App />
    </StrictMode>
  );

  expect(
    await screen.findByText(/no shipments found for your account/i)
  ).toBeInTheDocument();
  expect(authMeRequests).toBe(1);
});

test('keeps the create shipment view active after creating a shipping label', async () => {
  window.localStorage.setItem('auth_access_token', 'test-token');

  let authMeRequests = 0;

  global.fetch.mockImplementation(async (path, options = {}) => {
    if (path === '/auth/me') {
      authMeRequests += 1;

      return createJsonResponse({
        ok: true,
        payload: {
          data: {
            id: 7,
            name: 'Jane Doe',
            email: 'jane@example.com',
          },
        },
        status: 200,
      });
    }

    if (path === '/shipping-labels' && options.method === 'POST') {
      return createJsonResponse({
        ok: true,
        payload: {
          data: {
            id: 21,
            tracking_code: '9405500000000000000000',
            label_url: 'https://example.test/created-label.pdf',
            carrier: 'USPS',
            service: 'Ground Advantage',
            rate_amount: '7.42',
            rate_currency: 'USD',
            status: 'purchased',
            from_address: {
              name: 'Jane Sender',
              street1: '417 Montgomery Street',
              city: 'San Francisco',
              state: 'CA',
              zip: '94104',
              country: 'US',
            },
            to_address: {
              name: 'John Receiver',
              street1: '388 Townsend St',
              city: 'San Francisco',
              state: 'CA',
              zip: '94107',
              country: 'US',
            },
            parcel: {
              weight_oz: 12,
              length_in: 10,
              width_in: 7,
              height_in: 4,
            },
            created_at: '2026-04-06T15:00:00Z',
            easypost_shipment_id: 'shp_123',
            easypost_rate_id: 'rate_123',
          },
        },
        status: 201,
      });
    }

    if (path === '/shipping-labels') {
      return createJsonResponse({
        ok: true,
        payload: {
          data: [],
        },
        status: 200,
      });
    }

    throw new Error(`Unexpected request: ${path}`);
  });

  render(<App />);

  expect(
    await screen.findByText(/no shipments found for your account/i)
  ).toBeInTheDocument();

  fireEvent.click(screen.getByRole('tab', { name: /^new shipment$/i }));

  expect(
    screen.getByRole('heading', { name: /create shipping label/i })
  ).toBeInTheDocument();

  fireEvent.click(
    screen.getByRole('button', { name: /^create shipping label$/i })
  );

  expect(
    await screen.findByText(/shipping label created successfully/i)
  ).toBeInTheDocument();
  expect(screen.getByText('9405500000000000000000')).toBeInTheDocument();
  expect(
    screen.getByRole('tab', { name: /^new shipment$/i })
  ).toHaveAttribute('aria-selected', 'true');
  expect(authMeRequests).toBe(1);
});
