# Easy Post Integration

A full-stack prototype for user registration and USPS shipping label generation with EasyPost. Although the repository also includes a React UI, the implementation was concentrated in the `api` environment, where authentication, business rules, external integration, and data persistence live.

## Delivery focus

- Laravel 13 API with PHP 8.3+, organized into domain, application, and infrastructure layers.
- Stateful authentication with Laravel Sanctum to protect private endpoints.
- EasyPost integration handled exclusively in the backend.
- Shipping label persistence in MySQL with history isolated per user.

## Implementation overview

The backend was structured to separate business rules from framework details:

- `app/Core/Domain`: entities, value objects, exceptions, and the `ShippingLabelFactory`, responsible for normalizing payloads and applying core rules.
- `app/Core/Application`: handlers for authentication and shipping label use cases.
- `app/Infra/Integration`: the `EasyPostShippingLabelGateway`, which creates the shipment, interprets EasyPost responses, and purchases the label.
- `app/Infra/Persistence`: Eloquent repositories and mappers between the database and the domain.
- `app/Infra/Http`: JSON requests and controllers.

The JSON endpoints were kept in `routes/web.php` with `statefulApi()` enabled at bootstrap so session/cookie-based Sanctum authentication works well in the SPA scenario.

## DDD approach

The goal of using DDD in this project was to keep the center of the application in the business rules, not in the framework or the EasyPost integration. Since the main problem in this take-home is generating, purchasing, storing, and retrieving shipping labels with specific rules, the code was organized so those decisions stay in the domain and application layers, while HTTP, database, and external provider concerns remain infrastructure details.

In practice, that shows up in a few places:

- the domain owns the main concepts, such as `ShippingLabel`, `ShippingLabelQuote`, `PurchasedShippingLabel`, and business exceptions;
- use cases are explicit in handlers such as `CreateHandler`, `ListShippingLabelsHandler`, and `GetShippingLabelHandler`;
- contracts such as `ShippingLabelGateway`, `ShippingLabelRepository`, and `AuthenticationSession` decouple business rules from concrete implementations;
- infrastructure simply connects those ports to Laravel, Eloquent, Sanctum, and EasyPost;
- this separation also made testing easier, since flows could be validated with test doubles and in-memory repositories without always depending on real integrations.

For a take-home exercise, the goal was not to apply DDD ceremonially, but to use its principles to make the backend more readable, testable, and easier to evolve.

## Business rules

- only authenticated users can create, list, and retrieve labels;
- each user can only see their own labels;
- origin and destination only accept United States addresses;
- ZIP codes must follow the expected format for US addresses;
- label purchasing only considers USPS rates;
- when more than one USPS rate is available, the API automatically selects the lowest one;
- if no USPS rate is available, the API returns a controlled error;
- internal EasyPost identifiers are persisted in the database but not exposed in the public payload;
- the label URL returned by EasyPost is persisted and used as the access method for the file in this prototype;
- provider failures are translated into predictable HTTP responses.

## Main endpoints

- `POST /auth/signup`: creates a user and starts a session immediately.
- `POST /auth/login`: authenticates with email and password.
- `POST /auth/logout`: ends the current session.
- `GET /auth/me`: returns the authenticated user.
- `POST /shipping-labels`: creates a label from addresses and package dimensions.
- `GET /shipping-labels`: lists the authenticated user's history.
- `GET /shipping-labels/{id}`: returns the details of a specific user label.

## API routes

- `POST /auth/signup`
  Creates a new user account and authenticates the session right after sign-up.
- `POST /auth/login`
  Authenticates an existing user with email and password.
- `POST /auth/logout`
  Ends the current authenticated session.
- `GET /auth/me`
  Returns the authenticated user's data.
- `POST /shipping-labels`
  Creates a new shipping label by calling EasyPost and persisting the result.
- `GET /shipping-labels`
  Lists the shipping label history for the authenticated user.
- `GET /shipping-labels/{id}`
  Returns the details of a specific shipping label, as long as it belongs to the authenticated user.

## Use cases

- register user: creates a new account and authenticates the user immediately after;
- authenticate user: validates credentials and starts a session for private-route access;
- get authenticated user: returns the basic data from the current session;
- log out: invalidates the current authentication;
- create shipping label: validates the payload, normalizes address and package data, calls EasyPost, picks the lowest USPS rate, purchases the label, and persists the result;
- list label history: returns only the labels owned by the authenticated user;
- retrieve label details: fetches a specific label while respecting user isolation.

## Persistence

The `shipping_labels` table stores:

- the relationship to the user;
- EasyPost shipment/rate IDs;
- tracking code, carrier, service, amount, and currency;
- origin address, destination address, and package dimensions as JSON;
- the raw provider response for traceability.

## Quick start

1. Configure `api/.env` with at least `DB_*`, `APP_KEY`, `SESSION_DOMAIN`, `SANCTUM_STATEFUL_DOMAINS`, and `EASYPOST_API_KEY`.
2. Start the environment:

```bash
docker compose up --build -d
```

3. Install API dependencies and run migrations:

```bash
docker compose exec easypost-integration-api composer install
docker compose exec easypost-integration-api php artisan key:generate
docker compose exec easypost-integration-api php artisan migrate
```

4. Access:

- API: `http://localhost:3031`
- UI: `http://localhost:3000`

## Tests

The test suite covers authentication, label creation, user isolation, persistence mapping, and EasyPost error translation.

```bash
docker compose exec easypost-integration-api php artisan test
```

A Bruno collection is also available in `api/bruno/` for manual endpoint testing.

## Assumptions and next steps

Assumptions for this prototype:

- EasyPost test labels are sufficient to validate the flow;
- for this delivery, persisting `label_url` was enough to view/print the file later;
- the focus was to harden the API before refining the UI.

With more time, the next steps would be:

- downloading the PDF and storing it internally (private disk or S3 with signed URLs);
- pagination and filters for history;
- observability for the external integration and retry/idempotency policies;
- status synchronization through webhooks if the flow required later label updates.
