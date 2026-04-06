import { useEffect, useEffectEvent, useState } from 'react';
import { getAuthenticated, sign, sgnup, signout } from './api/auth';
import { createShippingLabel } from './api/shippingLabels';
import './App.css';

function createSignInInitialState() {
  return {
    email: '',
    password: '',
  };
}

function createSignUpInitialState() {
  return {
    name: '',
    email: '',
    password: '',
    passwordConfirmation: '',
  };
}

function createShippingLabelInitialState() {
  return {
    fromAddress: {
      name: 'Jane Sender',
      street1: '417 Montgomery Street',
      street2: '',
      city: 'San Francisco',
      state: 'CA',
      zip: '94104',
      country: 'US',
    },
    toAddress: {
      name: 'John Receiver',
      street1: '388 Townsend St',
      street2: '',
      city: 'San Francisco',
      state: 'CA',
      zip: '94107',
      country: 'US',
    },
    parcel: {
      weightOz: '12',
      lengthIn: '10',
      widthIn: '7',
      heightIn: '4',
    },
  };
}

const ENDPOINTS = [
  { action: 'sign', method: 'POST', path: '/auth/login' },
  { action: 'sgnup', method: 'POST', path: '/auth/signup' },
  { action: 'getAuthenticated', method: 'GET', path: '/auth/me' },
  { action: 'signout', method: 'POST', path: '/auth/logout' },
  {
    action: 'createShippingLabel',
    method: 'POST',
    path: '/shipping-labels',
  },
];

function getFirstError(errors, field) {
  const fieldErrors = errors[field];

  return Array.isArray(fieldErrors) ? fieldErrors[0] : '';
}

function normalizeNumber(value) {
  if (value === '') {
    return '';
  }

  const parsedValue = Number(value);

  return Number.isNaN(parsedValue) ? value : parsedValue;
}

function toShippingLabelPayload(form) {
  return {
    from_address: {
      ...form.fromAddress,
    },
    to_address: {
      ...form.toAddress,
    },
    parcel: {
      weight_oz: normalizeNumber(form.parcel.weightOz),
      length_in: normalizeNumber(form.parcel.lengthIn),
      width_in: normalizeNumber(form.parcel.widthIn),
      height_in: normalizeNumber(form.parcel.heightIn),
    },
  };
}

function formatAddress(address) {
  if (!address) {
    return '-';
  }

  return [
    address.name,
    address.street1,
    address.street2,
    `${address.city}, ${address.state} ${address.zip}`,
    address.country,
  ]
    .filter(Boolean)
    .join(' | ');
}

function formatMoney(amount, currency) {
  if (!amount) {
    return '-';
  }

  return currency ? `${amount} ${currency}` : amount;
}

function formatTimestamp(value) {
  if (!value) {
    return '-';
  }

  const parsedDate = new Date(value);

  if (Number.isNaN(parsedDate.getTime())) {
    return value;
  }

  return new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(parsedDate);
}

function App() {
  const [mode, setMode] = useState('sign');
  const [status, setStatus] = useState('loading');
  const [user, setUser] = useState(null);
  const [isAuthSubmitting, setIsAuthSubmitting] = useState(false);
  const [isLabelSubmitting, setIsLabelSubmitting] = useState(false);
  const [feedback, setFeedback] = useState(null);
  const [authFieldErrors, setAuthFieldErrors] = useState({});
  const [shippingFieldErrors, setShippingFieldErrors] = useState({});
  const [signInForm, setSignInForm] = useState(createSignInInitialState);
  const [signUpForm, setSignUpForm] = useState(createSignUpInitialState);
  const [shippingLabelForm, setShippingLabelForm] = useState(
    createShippingLabelInitialState
  );
  const [createdLabel, setCreatedLabel] = useState(null);

  const clearFeedback = () => {
    setFeedback(null);
  };

  const resetShippingWorkspace = () => {
    setShippingLabelForm(createShippingLabelInitialState());
    setShippingFieldErrors({});
    setCreatedLabel(null);
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
    clearFeedback();
    setAuthFieldErrors({});
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

  const handleShippingAddressChange = (section) => (event) => {
    const { name, value } = event.target;

    setShippingLabelForm((current) => ({
      ...current,
      [section]: {
        ...current[section],
        [name]: value,
      },
    }));
  };

  const handleParcelChange = (event) => {
    const { name, value } = event.target;

    setShippingLabelForm((current) => ({
      ...current,
      parcel: {
        ...current.parcel,
        [name]: value,
      },
    }));
  };

  const handleSignInSubmit = async (event) => {
    event.preventDefault();
    clearFeedback();
    setAuthFieldErrors({});
    setIsAuthSubmitting(true);

    try {
      const authenticatedUser = await sign({
        email: signInForm.email.trim(),
        password: signInForm.password,
      });

      setUser(authenticatedUser);
      setStatus('authenticated');
      setSignInForm(createSignInInitialState());
      resetShippingWorkspace();
      setFeedback({
        tone: 'success',
        message: 'Sessao iniciada com sucesso.',
      });
    } catch (error) {
      setAuthFieldErrors(error.errors || {});
      setFeedback({
        tone: 'danger',
        message: error.message || 'Falha ao autenticar o usuario.',
      });
    } finally {
      setIsAuthSubmitting(false);
    }
  };

  const handleSignUpSubmit = async (event) => {
    event.preventDefault();
    clearFeedback();
    setAuthFieldErrors({});
    setIsAuthSubmitting(true);

    try {
      const authenticatedUser = await sgnup({
        name: signUpForm.name.trim(),
        email: signUpForm.email.trim(),
        password: signUpForm.password,
        password_confirmation: signUpForm.passwordConfirmation,
      });

      setUser(authenticatedUser);
      setStatus('authenticated');
      setSignUpForm(createSignUpInitialState());
      resetShippingWorkspace();
      setFeedback({
        tone: 'success',
        message: 'Conta criada e autenticada com sucesso.',
      });
    } catch (error) {
      setAuthFieldErrors(error.errors || {});
      setFeedback({
        tone: 'danger',
        message: error.message || 'Falha ao criar a conta.',
      });
    } finally {
      setIsAuthSubmitting(false);
    }
  };

  const handleSignout = async () => {
    clearFeedback();
    setIsAuthSubmitting(true);

    try {
      await signout();

      setUser(null);
      setStatus('guest');
      setMode('sign');
      setAuthFieldErrors({});
      resetShippingWorkspace();
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
      setIsAuthSubmitting(false);
    }
  };

  const handleShippingLabelSubmit = async (event) => {
    event.preventDefault();
    clearFeedback();
    setShippingFieldErrors({});
    setIsLabelSubmitting(true);

    try {
      const shippingLabel = await createShippingLabel(
        toShippingLabelPayload(shippingLabelForm)
      );

      setCreatedLabel(shippingLabel);
      setShippingLabelForm(createShippingLabelInitialState());
      setFeedback({
        tone: 'success',
        message: 'Shipping label criada com sucesso.',
      });
    } catch (error) {
      if (error.status === 401) {
        setUser(null);
        setStatus('guest');
        setMode('sign');
        setCreatedLabel(null);
        setFeedback({
          tone: 'danger',
          message: 'Sua sessao expirou. Entre novamente para continuar.',
        });
        return;
      }

      setShippingFieldErrors(error.errors || {});
      setFeedback({
        tone: 'danger',
        message: error.message || 'Falha ao criar a shipping label.',
      });
    } finally {
      setIsLabelSubmitting(false);
    }
  };

  const isAuthenticated = status === 'authenticated' && user !== null;

  return (
    <main className="app-shell">
      <section className="hero-panel">
        <div className="hero-copy">
          <span className="eyebrow">Easy UI</span>
          <h1>ShippingLabels com compra autenticada e contrato real da API.</h1>
          <p className="hero-text">
            O frontend usa sessao via cookie, consulta a identidade atual e
            envia <code>from_address</code>, <code>to_address</code> e{' '}
            <code>parcel</code> para o endpoint de criacao em
            <code> api/routes/web.php</code>.
          </p>
        </div>

        <div className="endpoint-card">
          <div className="endpoint-card__header">
            <span>Contrato observado</span>
            <strong>Autenticacao e ShippingLabels</strong>
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
                    ? 'Criar ShippingLabels'
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
            <div className="workspace-panel">
              <div className="workspace-toolbar">
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

                <button
                  className="primary-button primary-button--danger"
                  disabled={isAuthSubmitting || isLabelSubmitting}
                  onClick={handleSignout}
                  type="button"
                >
                  {isAuthSubmitting ? 'Saindo...' : 'Signout'}
                </button>
              </div>

              <div className="profile-note">
                <code>POST /shipping-labels</code> cria o shipment, escolhe a
                menor tarifa USPS disponivel e devolve a etiqueta comprada com
                tracking, carrier, service e <code>label_url</code>.
              </div>

              <form className="shipping-form" onSubmit={handleShippingLabelSubmit}>
                <section className="form-section">
                  <div className="section-heading">
                    <p>Remetente</p>
                    <span>from_address</span>
                  </div>

                  <div className="field-grid field-grid--address">
                    <label className="field">
                      <span>Nome</span>
                      <input
                        name="name"
                        onChange={handleShippingAddressChange('fromAddress')}
                        placeholder="Jane Sender"
                        type="text"
                        value={shippingLabelForm.fromAddress.name}
                      />
                      {getFirstError(shippingFieldErrors, 'from_address.name') ? (
                        <small>
                          {getFirstError(shippingFieldErrors, 'from_address.name')}
                        </small>
                      ) : null}
                    </label>

                    <label className="field field--full">
                      <span>Street 1</span>
                      <input
                        name="street1"
                        onChange={handleShippingAddressChange('fromAddress')}
                        placeholder="417 Montgomery Street"
                        type="text"
                        value={shippingLabelForm.fromAddress.street1}
                      />
                      {getFirstError(shippingFieldErrors, 'from_address.street1') ? (
                        <small>
                          {getFirstError(
                            shippingFieldErrors,
                            'from_address.street1'
                          )}
                        </small>
                      ) : null}
                    </label>

                    <label className="field field--full">
                      <span>Street 2</span>
                      <input
                        name="street2"
                        onChange={handleShippingAddressChange('fromAddress')}
                        placeholder="Suite 400"
                        type="text"
                        value={shippingLabelForm.fromAddress.street2}
                      />
                    </label>

                    <label className="field">
                      <span>Cidade</span>
                      <input
                        name="city"
                        onChange={handleShippingAddressChange('fromAddress')}
                        placeholder="San Francisco"
                        type="text"
                        value={shippingLabelForm.fromAddress.city}
                      />
                      {getFirstError(shippingFieldErrors, 'from_address.city') ? (
                        <small>
                          {getFirstError(shippingFieldErrors, 'from_address.city')}
                        </small>
                      ) : null}
                    </label>

                    <label className="field">
                      <span>Estado</span>
                      <input
                        maxLength="2"
                        name="state"
                        onChange={handleShippingAddressChange('fromAddress')}
                        placeholder="CA"
                        type="text"
                        value={shippingLabelForm.fromAddress.state}
                      />
                      {getFirstError(shippingFieldErrors, 'from_address.state') ? (
                        <small>
                          {getFirstError(shippingFieldErrors, 'from_address.state')}
                        </small>
                      ) : null}
                    </label>

                    <label className="field">
                      <span>ZIP</span>
                      <input
                        inputMode="numeric"
                        name="zip"
                        onChange={handleShippingAddressChange('fromAddress')}
                        placeholder="94104"
                        type="text"
                        value={shippingLabelForm.fromAddress.zip}
                      />
                      {getFirstError(shippingFieldErrors, 'from_address.zip') ? (
                        <small>
                          {getFirstError(shippingFieldErrors, 'from_address.zip')}
                        </small>
                      ) : null}
                    </label>

                    <label className="field">
                      <span>Pais</span>
                      <select
                        name="country"
                        onChange={handleShippingAddressChange('fromAddress')}
                        value={shippingLabelForm.fromAddress.country}
                      >
                        <option value="US">US</option>
                      </select>
                      {getFirstError(shippingFieldErrors, 'from_address.country') ? (
                        <small>
                          {getFirstError(
                            shippingFieldErrors,
                            'from_address.country'
                          )}
                        </small>
                      ) : null}
                    </label>
                  </div>
                </section>

                <section className="form-section">
                  <div className="section-heading">
                    <p>Destinatario</p>
                    <span>to_address</span>
                  </div>

                  <div className="field-grid field-grid--address">
                    <label className="field">
                      <span>Nome</span>
                      <input
                        name="name"
                        onChange={handleShippingAddressChange('toAddress')}
                        placeholder="John Receiver"
                        type="text"
                        value={shippingLabelForm.toAddress.name}
                      />
                      {getFirstError(shippingFieldErrors, 'to_address.name') ? (
                        <small>
                          {getFirstError(shippingFieldErrors, 'to_address.name')}
                        </small>
                      ) : null}
                    </label>

                    <label className="field field--full">
                      <span>Street 1</span>
                      <input
                        name="street1"
                        onChange={handleShippingAddressChange('toAddress')}
                        placeholder="388 Townsend St"
                        type="text"
                        value={shippingLabelForm.toAddress.street1}
                      />
                      {getFirstError(shippingFieldErrors, 'to_address.street1') ? (
                        <small>
                          {getFirstError(shippingFieldErrors, 'to_address.street1')}
                        </small>
                      ) : null}
                    </label>

                    <label className="field field--full">
                      <span>Street 2</span>
                      <input
                        name="street2"
                        onChange={handleShippingAddressChange('toAddress')}
                        placeholder="Apartment 12"
                        type="text"
                        value={shippingLabelForm.toAddress.street2}
                      />
                    </label>

                    <label className="field">
                      <span>Cidade</span>
                      <input
                        name="city"
                        onChange={handleShippingAddressChange('toAddress')}
                        placeholder="San Francisco"
                        type="text"
                        value={shippingLabelForm.toAddress.city}
                      />
                      {getFirstError(shippingFieldErrors, 'to_address.city') ? (
                        <small>
                          {getFirstError(shippingFieldErrors, 'to_address.city')}
                        </small>
                      ) : null}
                    </label>

                    <label className="field">
                      <span>Estado</span>
                      <input
                        maxLength="2"
                        name="state"
                        onChange={handleShippingAddressChange('toAddress')}
                        placeholder="CA"
                        type="text"
                        value={shippingLabelForm.toAddress.state}
                      />
                      {getFirstError(shippingFieldErrors, 'to_address.state') ? (
                        <small>
                          {getFirstError(shippingFieldErrors, 'to_address.state')}
                        </small>
                      ) : null}
                    </label>

                    <label className="field">
                      <span>ZIP</span>
                      <input
                        inputMode="numeric"
                        name="zip"
                        onChange={handleShippingAddressChange('toAddress')}
                        placeholder="94107"
                        type="text"
                        value={shippingLabelForm.toAddress.zip}
                      />
                      {getFirstError(shippingFieldErrors, 'to_address.zip') ? (
                        <small>
                          {getFirstError(shippingFieldErrors, 'to_address.zip')}
                        </small>
                      ) : null}
                    </label>

                    <label className="field">
                      <span>Pais</span>
                      <select
                        name="country"
                        onChange={handleShippingAddressChange('toAddress')}
                        value={shippingLabelForm.toAddress.country}
                      >
                        <option value="US">US</option>
                      </select>
                      {getFirstError(shippingFieldErrors, 'to_address.country') ? (
                        <small>
                          {getFirstError(shippingFieldErrors, 'to_address.country')}
                        </small>
                      ) : null}
                    </label>
                  </div>
                </section>

                <section className="form-section">
                  <div className="section-heading">
                    <p>Pacote</p>
                    <span>parcel</span>
                  </div>

                  <div className="field-grid field-grid--parcel">
                    <label className="field">
                      <span>Peso (oz)</span>
                      <input
                        min="0"
                        name="weightOz"
                        onChange={handleParcelChange}
                        step="0.01"
                        type="number"
                        value={shippingLabelForm.parcel.weightOz}
                      />
                      {getFirstError(shippingFieldErrors, 'parcel.weight_oz') ? (
                        <small>
                          {getFirstError(shippingFieldErrors, 'parcel.weight_oz')}
                        </small>
                      ) : null}
                    </label>

                    <label className="field">
                      <span>Comprimento (in)</span>
                      <input
                        min="0"
                        name="lengthIn"
                        onChange={handleParcelChange}
                        step="0.01"
                        type="number"
                        value={shippingLabelForm.parcel.lengthIn}
                      />
                      {getFirstError(shippingFieldErrors, 'parcel.length_in') ? (
                        <small>
                          {getFirstError(shippingFieldErrors, 'parcel.length_in')}
                        </small>
                      ) : null}
                    </label>

                    <label className="field">
                      <span>Largura (in)</span>
                      <input
                        min="0"
                        name="widthIn"
                        onChange={handleParcelChange}
                        step="0.01"
                        type="number"
                        value={shippingLabelForm.parcel.widthIn}
                      />
                      {getFirstError(shippingFieldErrors, 'parcel.width_in') ? (
                        <small>
                          {getFirstError(shippingFieldErrors, 'parcel.width_in')}
                        </small>
                      ) : null}
                    </label>

                    <label className="field">
                      <span>Altura (in)</span>
                      <input
                        min="0"
                        name="heightIn"
                        onChange={handleParcelChange}
                        step="0.01"
                        type="number"
                        value={shippingLabelForm.parcel.heightIn}
                      />
                      {getFirstError(shippingFieldErrors, 'parcel.height_in') ? (
                        <small>
                          {getFirstError(shippingFieldErrors, 'parcel.height_in')}
                        </small>
                      ) : null}
                    </label>
                  </div>
                </section>

                <button
                  className="primary-button shipping-form__submit"
                  disabled={isLabelSubmitting || isAuthSubmitting}
                  type="submit"
                >
                  {isLabelSubmitting
                    ? 'Criando shipping label...'
                    : 'Criar shipping label'}
                </button>
              </form>

              {createdLabel ? (
                <section className="result-panel" aria-live="polite">
                  <div className="section-heading">
                    <p>Resultado</p>
                    <span>shipping_labels.data</span>
                  </div>

                  <div className="result-grid">
                    <article className="result-item">
                      <span>Status</span>
                      <strong>{createdLabel.status}</strong>
                    </article>
                    <article className="result-item">
                      <span>Tracking</span>
                      <strong>{createdLabel.tracking_code || '-'}</strong>
                    </article>
                    <article className="result-item">
                      <span>Servico</span>
                      <strong>
                        {createdLabel.carrier} / {createdLabel.service}
                      </strong>
                    </article>
                    <article className="result-item">
                      <span>Valor</span>
                      <strong>
                        {formatMoney(
                          createdLabel.rate_amount,
                          createdLabel.rate_currency
                        )}
                      </strong>
                    </article>
                    <article className="result-item result-item--wide">
                      <span>Origem</span>
                      <strong>{formatAddress(createdLabel.from_address)}</strong>
                    </article>
                    <article className="result-item result-item--wide">
                      <span>Destino</span>
                      <strong>{formatAddress(createdLabel.to_address)}</strong>
                    </article>
                    <article className="result-item">
                      <span>Shipment ID</span>
                      <strong>{createdLabel.easypost_shipment_id}</strong>
                    </article>
                    <article className="result-item">
                      <span>Rate ID</span>
                      <strong>{createdLabel.easypost_rate_id}</strong>
                    </article>
                    <article className="result-item result-item--wide">
                      <span>Criada em</span>
                      <strong>{formatTimestamp(createdLabel.created_at)}</strong>
                    </article>
                  </div>

                  {createdLabel.label_url ? (
                    <a
                      className="result-link"
                      href={createdLabel.label_url}
                      rel="noreferrer"
                      target="_blank"
                    >
                      Abrir label_url
                    </a>
                  ) : null}
                </section>
              ) : (
                <section className="result-panel result-panel--empty">
                  Preencha remetente, destinatario e pacote para criar a
                  ShippingLabel via <code>POST /shipping-labels</code>.
                </section>
              )}
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
                      {getFirstError(authFieldErrors, 'email') ? (
                        <small>{getFirstError(authFieldErrors, 'email')}</small>
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
                      {getFirstError(authFieldErrors, 'password') ? (
                        <small>{getFirstError(authFieldErrors, 'password')}</small>
                      ) : null}
                    </label>

                    <button
                      className="primary-button"
                      disabled={isAuthSubmitting}
                      type="submit"
                    >
                      {isAuthSubmitting ? 'Entrando...' : 'Entrar'}
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
                      {getFirstError(authFieldErrors, 'name') ? (
                        <small>{getFirstError(authFieldErrors, 'name')}</small>
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
                      {getFirstError(authFieldErrors, 'email') ? (
                        <small>{getFirstError(authFieldErrors, 'email')}</small>
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
                      {getFirstError(authFieldErrors, 'password') ? (
                        <small>{getFirstError(authFieldErrors, 'password')}</small>
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
                      disabled={isAuthSubmitting}
                      type="submit"
                    >
                      {isAuthSubmitting ? 'Criando conta...' : 'Criar conta'}
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
