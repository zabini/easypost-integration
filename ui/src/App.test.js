import { render, screen } from '@testing-library/react';
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
  global.fetch.mockResolvedValueOnce(
    createJsonResponse({
      ok: false,
      payload: { message: 'Unauthenticated.' },
      status: 401,
    })
  );

  render(<App />);

  expect(await screen.findByText(/acesse sua conta/i)).toBeInTheDocument();
  expect(screen.getByRole('button', { name: /entrar/i })).toBeInTheDocument();
  expect(screen.getByText('/auth/me')).toBeInTheDocument();
  expect(screen.getByText('/shipping-labels')).toBeInTheDocument();
});

test('renders shipping label workspace for authenticated users', async () => {
  global.fetch.mockResolvedValueOnce(
    createJsonResponse({
      ok: true,
      payload: {
        data: {
          id: 7,
          name: 'Jane Doe',
          email: 'jane@example.com',
        },
      },
      status: 200,
    })
  );

  render(<App />);

  expect(await screen.findByText(/criar shippinglabels/i)).toBeInTheDocument();
  expect(
    screen.getByRole('button', { name: /criar shipping label/i })
  ).toBeInTheDocument();
  expect(screen.getByDisplayValue('Jane Sender')).toBeInTheDocument();
  expect(screen.getAllByText(/post \/shipping-labels/i)).toHaveLength(2);
});
