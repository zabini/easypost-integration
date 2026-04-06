import { useEffect, useEffectEvent, useState } from 'react';
import { getAuthenticated, sign, sgnup, signout } from './api/auth';
import './App.css';

const SIGN_IN_INITIAL_STATE = {
  email: '',
  password: '',
};

const SIGN_UP_INITIAL_STATE = {
  name: '',
  email: '',
  password: '',
  passwordConfirmation: '',
};

const ENDPOINTS = [
  { action: 'sign', method: 'POST', path: '/auth/login' },
  { action: 'sgnup', method: 'POST', path: '/auth/signup' },
  { action: 'getAuthenticated', method: 'GET', path: '/auth/me' },
  { action: 'signout', method: 'POST', path: '/auth/logout' },
];

function getFirstError(errors, field) {
  const fieldErrors = errors[field];

  return Array.isArray(fieldErrors) ? fieldErrors[0] : '';
}

function App() {
  const [mode, setMode] = useState('sign');
  const [status, setStatus] = useState('loading');
  const [user, setUser] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [feedback, setFeedback] = useState(null);
  const [fieldErrors, setFieldErrors] = useState({});
  const [signInForm, setSignInForm] = useState(SIGN_IN_INITIAL_STATE);
  const [signUpForm, setSignUpForm] = useState(SIGN_UP_INITIAL_STATE);

  const clearMessages = () => {
    setFeedback(null);
    setFieldErrors({});
  };

  const loadAuthenticatedUser = useEffectEvent(async () => {
    try {
      const authenticatedUser = await getAuthenticated();

      setUser(authenticatedUser);
      setStatus('authenticated');
    } catch (error) {
      if (error.status === 401) {
        setUser(null);
        setStatus('guest');
        return;
      }

      setUser(null);
      setStatus('guest');
      setFeedback({
        tone: 'danger',
        message:
          error.message || 'Nao foi possivel validar a sessao com a API.',
      });
    }
  });

  useEffect(() => {
    loadAuthenticatedUser();
  }, [loadAuthenticatedUser]);

  const handleModeChange = (nextMode) => {
    clearMessages();
    setMode(nextMode);
  };

  const handleSignInChange = (event) => {
    const { name, value } = event.target;

    setSignInForm((current) => ({
      ...current,
      [name]: value,
    }));
  };

  const handleSignUpChange = (event) => {
    const { name, value } = event.target;

    setSignUpForm((current) => ({
      ...current,
      [name]: value,
    }));
  };

  const handleSignInSubmit = async (event) => {
    event.preventDefault();
    clearMessages();
    setIsSubmitting(true);

    try {
      const authenticatedUser = await sign({
        email: signInForm.email.trim(),
        password: signInForm.password,
      });

      setUser(authenticatedUser);
      setStatus('authenticated');
      setSignInForm(SIGN_IN_INITIAL_STATE);
      setFeedback({
        tone: 'success',
        message: 'Sessao iniciada com sucesso.',
      });
    } catch (error) {
      setFieldErrors(error.errors || {});
      setFeedback({
        tone: 'danger',
        message: error.message || 'Falha ao autenticar o usuario.',
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleSignUpSubmit = async (event) => {
    event.preventDefault();
    clearMessages();
    setIsSubmitting(true);

    try {
      const authenticatedUser = await sgnup({
        name: signUpForm.name.trim(),
        email: signUpForm.email.trim(),
        password: signUpForm.password,
        password_confirmation: signUpForm.passwordConfirmation,
      });

      setUser(authenticatedUser);
      setStatus('authenticated');
      setSignUpForm(SIGN_UP_INITIAL_STATE);
      setFeedback({
        tone: 'success',
        message: 'Conta criada e autenticada com sucesso.',
      });
    } catch (error) {
      setFieldErrors(error.errors || {});
      setFeedback({
        tone: 'danger',
        message: error.message || 'Falha ao criar a conta.',
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleSignout = async () => {
    clearMessages();
    setIsSubmitting(true);

    try {
      await signout();

      setUser(null);
      setStatus('guest');
      setMode('sign');
      setFeedback({
        tone: 'neutral',
        message: 'Sessao encerrada com sucesso.',
      });
    } catch (error) {
      setFeedback({
        tone: 'danger',
        message: error.message || 'Falha ao encerrar a sessao.',
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  const isAuthenticated = status === 'authenticated' && user !== null;

  return (
    <main className="app-shell">
      <section className="hero-panel">
        <div className="hero-copy">
          <span className="eyebrow">Easy UI</span>
          <h1>Painel de autenticacao conectado aos endpoints reais.</h1>
          <p className="hero-text">
            O frontend usa sessao via cookie, consulta a identidade atual em
            `getAuthenticated` e expoe as acoes de entrada, cadastro e saida sem
            mudar o contrato do backend.
          </p>
        </div>

        <div className="endpoint-card">
          <div className="endpoint-card__header">
            <span>Contrato observado</span>
            <strong>API de autenticacao</strong>
          </div>

          <ul className="endpoint-list">
            {ENDPOINTS.map((endpoint) => (
              <li className="endpoint-item" key={endpoint.action}>
                <span className="endpoint-chip">{endpoint.method}</span>
                <div>
                  <strong>{endpoint.action}</strong>
                  <p>{endpoint.path}</p>
                </div>
              </li>
            ))}
          </ul>
        </div>
      </section>

      <section className="auth-panel">
        <div className="auth-card">
          <div className="auth-card__topbar">
            <div>
              <p className="auth-card__label">Sessao atual</p>
              <h2>
                {status === 'loading'
                  ? 'Verificando autenticacao'
                  : isAuthenticated
                    ? 'Usuario autenticado'
                    : 'Acesse sua conta'}
              </h2>
            </div>
            <span className={`status-pill status-pill--${status}`}>
              {status === 'loading'
                ? 'checando'
                : isAuthenticated
                  ? 'ativo'
                  : 'visitante'}
            </span>
          </div>

          {feedback ? (
            <div className={`feedback feedback--${feedback.tone}`} role="status">
              {feedback.message}
            </div>
          ) : null}

          {isAuthenticated ? (
            <div className="profile-panel">
              <div className="profile-grid">
                <article className="profile-block">
                  <span>ID</span>
                  <strong>{user.id}</strong>
                </article>
                <article className="profile-block">
                  <span>Nome</span>
                  <strong>{user.name}</strong>
                </article>
                <article className="profile-block">
                  <span>Email</span>
                  <strong>{user.email}</strong>
                </article>
              </div>

              <div className="profile-note">
                `getAuthenticated` respondeu com os dados do usuario atualmente
                autenticado.
              </div>

              <button
                className="primary-button primary-button--danger"
                disabled={isSubmitting}
                onClick={handleSignout}
                type="button"
              >
                {isSubmitting ? 'Saindo...' : 'Signout'}
              </button>
            </div>
          ) : (
            <>
              <div className="mode-switch" role="tablist" aria-label="Modo">
                <button
                  aria-controls="sign-panel"
                  aria-selected={mode === 'sign'}
                  className={
                    mode === 'sign'
                      ? 'mode-switch__button is-active'
                      : 'mode-switch__button'
                  }
                  id="sign-tab"
                  onClick={() => handleModeChange('sign')}
                  role="tab"
                  type="button"
                >
                  Sign
                </button>
                <button
                  aria-controls="sgnup-panel"
                  aria-selected={mode === 'sgnup'}
                  className={
                    mode === 'sgnup'
                      ? 'mode-switch__button is-active'
                      : 'mode-switch__button'
                  }
                  id="sgnup-tab"
                  onClick={() => handleModeChange('sgnup')}
                  role="tab"
                  type="button"
                >
                  Sgnup
                </button>
              </div>

              {mode === 'sign' ? (
                <div
                  aria-labelledby="sign-tab"
                  id="sign-panel"
                  role="tabpanel"
                >
                  <form className="auth-form" onSubmit={handleSignInSubmit}>
                    <label className="field">
                      <span>Email</span>
                      <input
                        autoComplete="email"
                        name="email"
                        onChange={handleSignInChange}
                        placeholder="jane@example.com"
                        type="email"
                        value={signInForm.email}
                      />
                      {getFirstError(fieldErrors, 'email') ? (
                        <small>{getFirstError(fieldErrors, 'email')}</small>
                      ) : null}
                    </label>

                    <label className="field">
                      <span>Senha</span>
                      <input
                        autoComplete="current-password"
                        name="password"
                        onChange={handleSignInChange}
                        placeholder="Digite sua senha"
                        type="password"
                        value={signInForm.password}
                      />
                      {getFirstError(fieldErrors, 'password') ? (
                        <small>{getFirstError(fieldErrors, 'password')}</small>
                      ) : null}
                    </label>

                    <button
                      className="primary-button"
                      disabled={isSubmitting}
                      type="submit"
                    >
                      {isSubmitting ? 'Entrando...' : 'Entrar'}
                    </button>
                  </form>
                </div>
              ) : (
                <div
                  aria-labelledby="sgnup-tab"
                  id="sgnup-panel"
                  role="tabpanel"
                >
                  <form className="auth-form" onSubmit={handleSignUpSubmit}>
                    <label className="field">
                      <span>Nome</span>
                      <input
                        autoComplete="name"
                        name="name"
                        onChange={handleSignUpChange}
                        placeholder="Jane Doe"
                        type="text"
                        value={signUpForm.name}
                      />
                      {getFirstError(fieldErrors, 'name') ? (
                        <small>{getFirstError(fieldErrors, 'name')}</small>
                      ) : null}
                    </label>

                    <label className="field">
                      <span>Email</span>
                      <input
                        autoComplete="email"
                        name="email"
                        onChange={handleSignUpChange}
                        placeholder="jane@example.com"
                        type="email"
                        value={signUpForm.email}
                      />
                      {getFirstError(fieldErrors, 'email') ? (
                        <small>{getFirstError(fieldErrors, 'email')}</small>
                      ) : null}
                    </label>

                    <label className="field">
                      <span>Senha</span>
                      <input
                        autoComplete="new-password"
                        name="password"
                        onChange={handleSignUpChange}
                        placeholder="Minimo de 8 caracteres"
                        type="password"
                        value={signUpForm.password}
                      />
                      {getFirstError(fieldErrors, 'password') ? (
                        <small>{getFirstError(fieldErrors, 'password')}</small>
                      ) : null}
                    </label>

                    <label className="field">
                      <span>Confirmacao de senha</span>
                      <input
                        autoComplete="new-password"
                        name="passwordConfirmation"
                        onChange={handleSignUpChange}
                        placeholder="Repita a senha"
                        type="password"
                        value={signUpForm.passwordConfirmation}
                      />
                    </label>

                    <button
                      className="primary-button"
                      disabled={isSubmitting}
                      type="submit"
                    >
                      {isSubmitting ? 'Criando conta...' : 'Criar conta'}
                    </button>
                  </form>
                </div>
              )}
            </>
          )}
        </div>
      </section>
    </main>
  );
}

export default App;
