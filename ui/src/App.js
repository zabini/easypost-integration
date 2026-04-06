import { useEffect, useEffectEvent, useState } from 'react';
import { getAuthenticated, sign, sgnup, signout } from './api/auth';
import {
  createShippingLabel,
  listShippingLabels,
} from './api/shippingLabels';
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

  return new Intl.DateTimeFormat('en-US', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(parsedDate);
}

function formatParcel(parcel) {
  if (!parcel) {
    return '-';
  }

  return `${parcel.weight_oz ?? '-'} oz | ${parcel.length_in ?? '-'} x ${
    parcel.width_in ?? '-'
  } x ${parcel.height_in ?? '-'} in`;
}

function App() {
  const [mode, setMode] = useState('sign');
  const [status, setStatus] = useState('loading');
  const [user, setUser] = useState(null);
  const [workspaceView, setWorkspaceView] = useState('list');
  const [isAuthSubmitting, setIsAuthSubmitting] = useState(false);
  const [isLabelSubmitting, setIsLabelSubmitting] = useState(false);
  const [isShippingLabelsLoading, setIsShippingLabelsLoading] = useState(false);
  const [feedback, setFeedback] = useState(null);
  const [authFieldErrors, setAuthFieldErrors] = useState({});
  const [shippingFieldErrors, setShippingFieldErrors] = useState({});
  const [signInForm, setSignInForm] = useState(createSignInInitialState);
  const [signUpForm, setSignUpForm] = useState(createSignUpInitialState);
  const [shippingLabelForm, setShippingLabelForm] = useState(
    createShippingLabelInitialState
  );
  const [createdLabel, setCreatedLabel] = useState(null);
  const [shippingLabels, setShippingLabels] = useState([]);
  const [hasLoadedShippingLabels, setHasLoadedShippingLabels] = useState(false);

  const clearFeedback = () => {
    setFeedback(null);
  };

  const resetShippingWorkspace = () => {
    setShippingLabelForm(createShippingLabelInitialState());
    setShippingFieldErrors({});
    setCreatedLabel(null);
  };

  const resetShippingLabelsState = () => {
    setShippingLabels([]);
    setHasLoadedShippingLabels(false);
    setIsShippingLabelsLoading(false);
  };

  const invalidateShippingLabels = () => {
    setHasLoadedShippingLabels(false);
  };

  const loadAuthenticatedUser = useEffectEvent(async () => {
    try {
      const authenticatedUser = await getAuthenticated();

      setUser(authenticatedUser);
      setStatus('authenticated');
      setWorkspaceView('list');
    } catch (error) {
      if (error.status === 401) {
        setUser(null);
        setStatus('guest');
        resetShippingLabelsState();
        return;
      }

      setUser(null);
      setStatus('guest');
      resetShippingLabelsState();
      setFeedback({
        tone: 'danger',
        message:
          error.message || 'Unable to validate the session with the API.',
      });
    }
  });

  useEffect(() => {
    loadAuthenticatedUser();
  }, [loadAuthenticatedUser]);

  const loadShippingLabels = useEffectEvent(async () => {
    setIsShippingLabelsLoading(true);

    try {
      const labels = await listShippingLabels();

      setShippingLabels(labels);
      setHasLoadedShippingLabels(true);
    } catch (error) {
      if (error.status === 401) {
        setUser(null);
        setStatus('guest');
        setMode('sign');
        setWorkspaceView('list');
        resetShippingWorkspace();
        resetShippingLabelsState();
        setFeedback({
          tone: 'danger',
          message: 'Your session has expired. Sign in again to continue.',
        });
        return;
      }

      setFeedback({
        tone: 'danger',
        message: error.message || 'Failed to load your shipments.',
      });
      setHasLoadedShippingLabels(true);
    } finally {
      setIsShippingLabelsLoading(false);
    }
  });

  const handleModeChange = (nextMode) => {
    clearFeedback();
    setAuthFieldErrors({});
    setMode(nextMode);
  };

  const handleWorkspaceViewChange = (nextView) => {
    setWorkspaceView(nextView);
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
      setWorkspaceView('list');
      setSignInForm(createSignInInitialState());
      resetShippingWorkspace();
      resetShippingLabelsState();
      setFeedback({
        tone: 'success',
        message: 'Signed in successfully.',
      });
    } catch (error) {
      setAuthFieldErrors(error.errors || {});
      setFeedback({
        tone: 'danger',
        message: error.message || 'Failed to sign in.',
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
      setWorkspaceView('list');
      setSignUpForm(createSignUpInitialState());
      resetShippingWorkspace();
      resetShippingLabelsState();
      setFeedback({
        tone: 'success',
        message: 'Account created and signed in successfully.',
      });
    } catch (error) {
      setAuthFieldErrors(error.errors || {});
      setFeedback({
        tone: 'danger',
        message: error.message || 'Failed to create the account.',
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
      setWorkspaceView('list');
      setAuthFieldErrors({});
      resetShippingWorkspace();
      resetShippingLabelsState();
      setFeedback({
        tone: 'neutral',
        message: 'Signed out successfully.',
      });
    } catch (error) {
      setFeedback({
        tone: 'danger',
        message: error.message || 'Failed to sign out.',
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
      invalidateShippingLabels();
      setFeedback({
        tone: 'success',
        message: 'Shipping label created successfully.',
      });
    } catch (error) {
      if (error.status === 401) {
        setUser(null);
        setStatus('guest');
        setMode('sign');
        setWorkspaceView('list');
        setCreatedLabel(null);
        resetShippingLabelsState();
        setFeedback({
          tone: 'danger',
          message: 'Your session has expired. Sign in again to continue.',
        });
        return;
      }

      setShippingFieldErrors(error.errors || {});
      setFeedback({
        tone: 'danger',
        message: error.message || 'Failed to create the shipping label.',
      });
    } finally {
      setIsLabelSubmitting(false);
    }
  };

  const isAuthenticated = status === 'authenticated' && user !== null;

  useEffect(() => {
    if (
      !isAuthenticated ||
      workspaceView !== 'list' ||
      hasLoadedShippingLabels
    ) {
      return;
    }

    loadShippingLabels();
  }, [
    hasLoadedShippingLabels,
    isAuthenticated,
    loadShippingLabels,
    workspaceView,
  ]);

  return (
    <main className="app-shell">
      <section className="auth-panel">
        <div className="auth-card">
          <div className="auth-card__topbar">
            <div>
              <p className="auth-card__label">Current session</p>
              <h2>
                {status === 'loading'
                  ? 'Checking authentication'
                  : isAuthenticated
                    ? workspaceView === 'list'
                      ? 'My shipments'
                      : 'Create shipping label'
                    : 'Access your account'}
              </h2>
            </div>
            <span className={`status-pill status-pill--${status}`}>
              {status === 'loading'
                ? 'checking'
                : isAuthenticated
                  ? 'active'
                  : 'guest'}
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
                    <span>Name</span>
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
                  {isAuthSubmitting ? 'Signing out...' : 'Sign out'}
                </button>
              </div>

              <div className="workspace-nav" role="tablist" aria-label="Views">
                <button
                  aria-selected={workspaceView === 'list'}
                  className={
                    workspaceView === 'list'
                      ? 'workspace-nav__button is-active'
                      : 'workspace-nav__button'
                  }
                  onClick={() => handleWorkspaceViewChange('list')}
                  role="tab"
                  type="button"
                >
                  Shipment list
                </button>
                <button
                  aria-selected={workspaceView === 'create'}
                  className={
                    workspaceView === 'create'
                      ? 'workspace-nav__button is-active'
                      : 'workspace-nav__button'
                  }
                  onClick={() => handleWorkspaceViewChange('create')}
                  role="tab"
                  type="button"
                >
                  New shipment
                </button>
              </div>

              {workspaceView === 'list' ? (
                <section className="shipping-list-panel" aria-live="polite">
                  <div className="section-heading">
                    <p>My shipments</p>
                    <div className="section-heading__actions">
                      <span>GET /shipping-labels</span>
                      <button
                        className="secondary-button"
                        disabled={isShippingLabelsLoading || isLabelSubmitting}
                        onClick={() => {
                          clearFeedback();
                          invalidateShippingLabels();
                        }}
                        type="button"
                      >
                        {isShippingLabelsLoading ? 'Refreshing...' : 'Refresh'}
                      </button>
                    </div>
                  </div>

                  {isShippingLabelsLoading ? (
                    <div className="empty-state">
                      Loading your saved shipments...
                    </div>
                  ) : shippingLabels.length > 0 ? (
                    <div className="shipping-list">
                      {shippingLabels.map((shippingLabel) => (
                        <article className="shipping-card" key={shippingLabel.id}>
                          <div className="shipping-card__header">
                            <div>
                              <p className="shipping-card__eyebrow">
                                Shipment #{shippingLabel.id}
                              </p>
                              <h3>
                                {shippingLabel.tracking_code || 'Tracking pending'}
                              </h3>
                            </div>
                            <span className="shipping-card__status">
                              {shippingLabel.status}
                            </span>
                          </div>

                          <div className="result-grid">
                            <article className="result-item">
                              <span>Service</span>
                              <strong>
                                {shippingLabel.carrier} / {shippingLabel.service}
                              </strong>
                            </article>
                            <article className="result-item">
                              <span>Rate</span>
                              <strong>
                                {formatMoney(
                                  shippingLabel.rate_amount,
                                  shippingLabel.rate_currency
                                )}
                              </strong>
                            </article>
                            <article className="result-item">
                              <span>Parcel</span>
                              <strong>{formatParcel(shippingLabel.parcel)}</strong>
                            </article>
                            <article className="result-item">
                              <span>Created at</span>
                              <strong>
                                {formatTimestamp(shippingLabel.created_at)}
                              </strong>
                            </article>
                            <article className="result-item result-item--wide">
                              <span>Origin</span>
                              <strong>
                                {formatAddress(shippingLabel.from_address)}
                              </strong>
                            </article>
                            <article className="result-item result-item--wide">
                              <span>Destination</span>
                              <strong>
                                {formatAddress(shippingLabel.to_address)}
                              </strong>
                            </article>
                          </div>

                          {shippingLabel.label_url ? (
                            <a
                              className="result-link"
                              href={shippingLabel.label_url}
                              rel="noreferrer"
                              target="_blank"
                            >
                              Open label
                            </a>
                          ) : null}
                        </article>
                      ))}
                    </div>
                  ) : (
                    <div className="empty-state">
                      <p>No shipments found for your account.</p>
                      <button
                        className="primary-button empty-state__button"
                        onClick={() => handleWorkspaceViewChange('create')}
                        type="button"
                      >
                        Create new shipment
                      </button>
                    </div>
                  )}
                </section>
              ) : (
                <>
                  <div className="profile-note">
                    <code>POST /shipping-labels</code> creates the shipment,
                    selects the lowest available USPS rate, and returns the
                    purchased label with tracking, carrier, service, and{' '}
                    <code>label_url</code>.
                  </div>

                  <form
                    className="shipping-form"
                    onSubmit={handleShippingLabelSubmit}
                  >
                    <section className="form-section">
                      <div className="section-heading">
                        <p>Sender</p>
                        <span>from_address</span>
                      </div>

                      <div className="field-grid field-grid--address">
                        <label className="field">
                          <span>Name</span>
                          <input
                            name="name"
                            onChange={handleShippingAddressChange('fromAddress')}
                            placeholder="Jane Sender"
                            type="text"
                            value={shippingLabelForm.fromAddress.name}
                          />
                          {getFirstError(
                            shippingFieldErrors,
                            'from_address.name'
                          ) ? (
                            <small>
                              {getFirstError(
                                shippingFieldErrors,
                                'from_address.name'
                              )}
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
                          {getFirstError(
                            shippingFieldErrors,
                            'from_address.street1'
                          ) ? (
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
                          <span>City</span>
                          <input
                            name="city"
                            onChange={handleShippingAddressChange('fromAddress')}
                            placeholder="San Francisco"
                            type="text"
                            value={shippingLabelForm.fromAddress.city}
                          />
                          {getFirstError(
                            shippingFieldErrors,
                            'from_address.city'
                          ) ? (
                            <small>
                              {getFirstError(
                                shippingFieldErrors,
                                'from_address.city'
                              )}
                            </small>
                          ) : null}
                        </label>

                        <label className="field">
                          <span>State</span>
                          <input
                            maxLength="2"
                            name="state"
                            onChange={handleShippingAddressChange('fromAddress')}
                            placeholder="CA"
                            type="text"
                            value={shippingLabelForm.fromAddress.state}
                          />
                          {getFirstError(
                            shippingFieldErrors,
                            'from_address.state'
                          ) ? (
                            <small>
                              {getFirstError(
                                shippingFieldErrors,
                                'from_address.state'
                              )}
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
                          {getFirstError(
                            shippingFieldErrors,
                            'from_address.zip'
                          ) ? (
                            <small>
                              {getFirstError(
                                shippingFieldErrors,
                                'from_address.zip'
                              )}
                            </small>
                          ) : null}
                        </label>

                        <label className="field">
                          <span>Country</span>
                          <select
                            name="country"
                            onChange={handleShippingAddressChange('fromAddress')}
                            value={shippingLabelForm.fromAddress.country}
                          >
                            <option value="US">US</option>
                          </select>
                          {getFirstError(
                            shippingFieldErrors,
                            'from_address.country'
                          ) ? (
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
                        <p>Recipient</p>
                        <span>to_address</span>
                      </div>

                      <div className="field-grid field-grid--address">
                        <label className="field">
                          <span>Name</span>
                          <input
                            name="name"
                            onChange={handleShippingAddressChange('toAddress')}
                            placeholder="John Receiver"
                            type="text"
                            value={shippingLabelForm.toAddress.name}
                          />
                          {getFirstError(
                            shippingFieldErrors,
                            'to_address.name'
                          ) ? (
                            <small>
                              {getFirstError(
                                shippingFieldErrors,
                                'to_address.name'
                              )}
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
                          {getFirstError(
                            shippingFieldErrors,
                            'to_address.street1'
                          ) ? (
                            <small>
                              {getFirstError(
                                shippingFieldErrors,
                                'to_address.street1'
                              )}
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
                          <span>City</span>
                          <input
                            name="city"
                            onChange={handleShippingAddressChange('toAddress')}
                            placeholder="San Francisco"
                            type="text"
                            value={shippingLabelForm.toAddress.city}
                          />
                          {getFirstError(
                            shippingFieldErrors,
                            'to_address.city'
                          ) ? (
                            <small>
                              {getFirstError(
                                shippingFieldErrors,
                                'to_address.city'
                              )}
                            </small>
                          ) : null}
                        </label>

                        <label className="field">
                          <span>State</span>
                          <input
                            maxLength="2"
                            name="state"
                            onChange={handleShippingAddressChange('toAddress')}
                            placeholder="CA"
                            type="text"
                            value={shippingLabelForm.toAddress.state}
                          />
                          {getFirstError(
                            shippingFieldErrors,
                            'to_address.state'
                          ) ? (
                            <small>
                              {getFirstError(
                                shippingFieldErrors,
                                'to_address.state'
                              )}
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
                          {getFirstError(
                            shippingFieldErrors,
                            'to_address.zip'
                          ) ? (
                            <small>
                              {getFirstError(
                                shippingFieldErrors,
                                'to_address.zip'
                              )}
                            </small>
                          ) : null}
                        </label>

                        <label className="field">
                          <span>Country</span>
                          <select
                            name="country"
                            onChange={handleShippingAddressChange('toAddress')}
                            value={shippingLabelForm.toAddress.country}
                          >
                            <option value="US">US</option>
                          </select>
                          {getFirstError(
                            shippingFieldErrors,
                            'to_address.country'
                          ) ? (
                            <small>
                              {getFirstError(
                                shippingFieldErrors,
                                'to_address.country'
                              )}
                            </small>
                          ) : null}
                        </label>
                      </div>
                    </section>

                    <section className="form-section">
                      <div className="section-heading">
                        <p>Parcel</p>
                        <span>parcel</span>
                      </div>

                      <div className="field-grid field-grid--parcel">
                        <label className="field">
                          <span>Weight (oz)</span>
                          <input
                            min="0"
                            name="weightOz"
                            onChange={handleParcelChange}
                            step="0.01"
                            type="number"
                            value={shippingLabelForm.parcel.weightOz}
                          />
                          {getFirstError(
                            shippingFieldErrors,
                            'parcel.weight_oz'
                          ) ? (
                            <small>
                              {getFirstError(
                                shippingFieldErrors,
                                'parcel.weight_oz'
                              )}
                            </small>
                          ) : null}
                        </label>

                        <label className="field">
                          <span>Length (in)</span>
                          <input
                            min="0"
                            name="lengthIn"
                            onChange={handleParcelChange}
                            step="0.01"
                            type="number"
                            value={shippingLabelForm.parcel.lengthIn}
                          />
                          {getFirstError(
                            shippingFieldErrors,
                            'parcel.length_in'
                          ) ? (
                            <small>
                              {getFirstError(
                                shippingFieldErrors,
                                'parcel.length_in'
                              )}
                            </small>
                          ) : null}
                        </label>

                        <label className="field">
                          <span>Width (in)</span>
                          <input
                            min="0"
                            name="widthIn"
                            onChange={handleParcelChange}
                            step="0.01"
                            type="number"
                            value={shippingLabelForm.parcel.widthIn}
                          />
                          {getFirstError(
                            shippingFieldErrors,
                            'parcel.width_in'
                          ) ? (
                            <small>
                              {getFirstError(
                                shippingFieldErrors,
                                'parcel.width_in'
                              )}
                            </small>
                          ) : null}
                        </label>

                        <label className="field">
                          <span>Height (in)</span>
                          <input
                            min="0"
                            name="heightIn"
                            onChange={handleParcelChange}
                            step="0.01"
                            type="number"
                            value={shippingLabelForm.parcel.heightIn}
                          />
                          {getFirstError(
                            shippingFieldErrors,
                            'parcel.height_in'
                          ) ? (
                            <small>
                              {getFirstError(
                                shippingFieldErrors,
                                'parcel.height_in'
                              )}
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
                        ? 'Creating shipping label...'
                        : 'Create shipping label'}
                    </button>
                  </form>

                  {createdLabel ? (
                    <section className="result-panel" aria-live="polite">
                      <div className="section-heading">
                        <p>Result</p>
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
                          <span>Service</span>
                          <strong>
                            {createdLabel.carrier} / {createdLabel.service}
                          </strong>
                        </article>
                        <article className="result-item">
                          <span>Rate</span>
                          <strong>
                            {formatMoney(
                              createdLabel.rate_amount,
                              createdLabel.rate_currency
                            )}
                          </strong>
                        </article>
                        <article className="result-item result-item--wide">
                          <span>Origin</span>
                          <strong>
                            {formatAddress(createdLabel.from_address)}
                          </strong>
                        </article>
                        <article className="result-item result-item--wide">
                          <span>Destination</span>
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
                          <span>Created at</span>
                          <strong>
                            {formatTimestamp(createdLabel.created_at)}
                          </strong>
                        </article>
                      </div>

                      {createdLabel.label_url ? (
                        <a
                          className="result-link"
                          href={createdLabel.label_url}
                          rel="noreferrer"
                          target="_blank"
                        >
                          Open label URL
                        </a>
                      ) : null}
                    </section>
                  ) : (
                    <section className="result-panel result-panel--empty">
                      Fill in sender, recipient, and parcel to create the
                      shipping label via <code>POST /shipping-labels</code>.
                    </section>
                  )}
                </>
              )}
            </div>
          ) : (
            <>
              <div className="mode-switch" role="tablist" aria-label="Mode">
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
                  Sign in
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
                  Sign up
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
                      <span>Password</span>
                      <input
                        autoComplete="current-password"
                        name="password"
                        onChange={handleSignInChange}
                        placeholder="Enter your password"
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
                      {isAuthSubmitting ? 'Signing in...' : 'Sign in'}
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
                      <span>Name</span>
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
                      <span>Password</span>
                      <input
                        autoComplete="new-password"
                        name="password"
                        onChange={handleSignUpChange}
                        placeholder="Minimum 8 characters"
                        type="password"
                        value={signUpForm.password}
                      />
                      {getFirstError(authFieldErrors, 'password') ? (
                        <small>{getFirstError(authFieldErrors, 'password')}</small>
                      ) : null}
                    </label>

                    <label className="field">
                      <span>Password confirmation</span>
                      <input
                        autoComplete="new-password"
                        name="passwordConfirmation"
                        onChange={handleSignUpChange}
                        placeholder="Repeat the password"
                        type="password"
                        value={signUpForm.passwordConfirmation}
                      />
                    </label>

                    <button
                      className="primary-button"
                      disabled={isAuthSubmitting}
                      type="submit"
                    >
                      {isAuthSubmitting ? 'Creating account...' : 'Create account'}
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
