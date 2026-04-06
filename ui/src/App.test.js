import { render, screen } from '@testing-library/react';
import App from './App';

beforeEach(() => {
  global.fetch = jest.fn().mockResolvedValue({
    ok: false,
    status: 401,
    headers: {
      get: () => 'application/json',
    },
    json: async () => ({
      message: 'Unauthenticated.',
    }),
    text: async () => JSON.stringify({
      message: 'Unauthenticated.',
    }),
  });
});

afterEach(() => {
  jest.restoreAllMocks();
});

test('renders sign in form for guest users', async () => {
  render(<App />);

  expect(await screen.findByText(/acesse sua conta/i)).toBeInTheDocument();
  expect(screen.getByRole('button', { name: /entrar/i })).toBeInTheDocument();
  expect(screen.getByText('/auth/me')).toBeInTheDocument();
});
