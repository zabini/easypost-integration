import { fireEvent, render, screen } from '@testing-library/react';
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
  global.fetch.mockImplementation(async (path) => {
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
});

test('redirects to list after sign in', async () => {
  global.fetch.mockImplementation(async (path) => {
    if (path === '/auth/me') {
      return createJsonResponse({
        ok: false,
        payload: { message: 'Unauthenticated.' },
        status: 401,
      });
    }

    if (path === '/sanctum/csrf-cookie') {
      return createJsonResponse({
        ok: true,
        payload: null,
        status: 204,
      });
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
    screen.getByText(/no shipments found for your account/i)
  ).toBeInTheDocument();
});
