# Development Rules for New Projects

This document is prescriptive.

It must be used as a construction rule for new projects that want to maintain the same development, architecture, and organization standards adopted in this repository.

The goal here is not to explain the origin of the standard. The goal is to define how the project must be built.

## 1. Mandatory principles

The rules below must guide every new feature:

- clearly separate domain, application, and infrastructure
- keep business rules in the core
- use explicit use cases for each relevant action
- depend on contracts in the core and implementations in the infrastructure
- avoid direct coupling between business rules and the framework
- maintain high cohesion by module and low coupling between layers
- favor testability from the design stage
- use events to decouple flow steps, not to hide complexity

## 2. Standard folder structure

Every new project must follow this base structure:

```text
app/
  Core/
    Domain/
      Contracts/
        Enum/
        Event/
      Exceptions/
      <Module>/
      <SharedEntities>.php
    Application/
      <Module>/
        <UseCase>.php
        <UseCase>Handler.php
  Infra/
    Http/
      Controller/
      Request/
      Exception/
    Persistence/
    ORM/
    Event/
    Async/
    Integration/
      Http/
        Client/
        Response/
test/
  Cases/
    Unit/
      Core/
      Infra/
    Integration/
      Application/
      Http/
  Doubles/
docs/
```

## 3. Architectural rules

### 3.1 Core

`app/Core` contains only:

- domain
- contracts
- use cases
- business exceptions
- domain events

`Core` must not know about:

- ORM
- controllers
- HTTP requests
- Guzzle
- Redis
- framework models

### 3.2 Infra

`app/Infra` contains only adapters:

- HTTP
- persistence
- queue
- external integrations
- concrete event publishing

`Infra` can depend on `Core`.

`Core` must not depend on `Infra`.

### 3.3 Dependency direction

All dependencies must point inward:

- controller -> handler
- handler -> domain contracts/entities
- infrastructure -> domain contracts

Never do:

- controller -> ORM
- domain -> HTTP framework
- handler -> concrete HTTP client
- handler -> ORM model

## 4. Rules for domain modeling

### 4.1 Entities

Entities must:

- have explicit identity
- encapsulate relevant behavior
- protect invariants
- avoid generic setters

Entities must not:

- know about the database
- know about requests/responses
- know serialization details

### 4.2 Enums

Use enums for:

- states
- types
- closed categories

Do not spread magic strings across the system.

### 4.3 Factories

Use factories when creation:

- requires consistency across more than one object
- depends on a polymorphic choice
- needs initial structural validation

Do not use a factory when a simple constructor clearly solves the problem.

### 4.4 Value Objects

In new projects, prefer introducing value objects from the start when the data has its own rules, for example:

- Money
- Email
- Document
- business IDs

If you choose not to use value objects at first, the minimum rule is:

- never leave important validations scattered across controllers
- centralize validations in the domain or the factory

## 5. Rules for use cases

Each use case must have:

- an input command
- a dedicated handler

### 5.1 Command

The command must:

- contain only input data
- be simple
- have a name oriented to business intent

Examples:

- `Create`
- `Deposit`
- `Transfer`
- `ProcessTransfer`

The command must not:

- execute rules
- call repositories
- know about the container

### 5.2 Handler

The handler must:

- receive explicit dependencies
- load the required entities
- orchestrate the flow
- persist changes
- publish events when there are follow-up actions

The handler must not:

- contain HTTP payload validation
- build HTTP responses
- manipulate ORM details

### 5.3 Standard implementation flow

Every new handler must follow this mental order:

1. Load the current state.
2. Validate preconditions.
3. Execute the business rule.
4. Persist the new state.
5. Publish an event, if applicable.
6. Return a simple result.

#### Snippet: command + handler

```php
<?php

declare(strict_types=1);

namespace App\Core\Application\Order;

final class Create
{
    public function __construct(
        private string $customerId,
        private int $amountInCents,
    ) {
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function getAmountInCents(): int
    {
        return $this->amountInCents;
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Core\Application\Order;

use App\Core\Domain\Contracts\Event\Publisher;
use App\Core\Domain\Contracts\OrderRepository;
use App\Core\Domain\Event\Order\Created;
use App\Core\Domain\Order\Order;

final class CreateHandler
{
    public function __construct(
        private OrderRepository $orders,
        private Publisher $publisher,
    ) {
    }

    public function handle(Create $command): string
    {
        $order = Order::create(
            customerId: $command->getCustomerId(),
            amountInCents: $command->getAmountInCents(),
        );

        $this->orders->save($order);
        $this->publisher->publish(new Created($order->getId()));

        return $order->getId();
    }
}
```

## 6. Rules for repositories

### 6.1 Contracts

Every repository must be defined in `app/Core/Domain/Contracts`.

Contracts must expose small and clear operations, for example:

- `save`
- `getOneById`
- `getOneOrNullBy...`

Avoid generic and vague contracts such as:

- `find`
- `all`
- `update`

without semantic context.

### 6.2 Concrete implementations

Every concrete implementation must live in `app/Infra/Persistence`.

The concrete repository must:

- map ORM to entity
- map entity to persistence
- hide query details
- return domain objects

The concrete repository must not:

- leak ORM models outward
- return raw arrays when the domain expects an entity

### 6.3 Hydration rule

If an entity depends on derived state to operate correctly, that state must be rebuilt during hydration.

Do not create partially assembled entities if that compromises domain rules.

#### Snippet: repository contract

```php
<?php

declare(strict_types=1);

namespace App\Core\Domain\Contracts;

use App\Core\Domain\Order\Order;

interface OrderRepository
{
    public function save(Order $order): void;

    public function getOneById(string $id): Order;

    public function getOneOrNullByReference(string $reference): ?Order;
}
```

#### Snippet: concrete implementation

```php
<?php

declare(strict_types=1);

namespace App\Infra\Persistence;

use App\Core\Domain\Contracts\OrderRepository as OrderRepositoryContract;
use App\Core\Domain\Exceptions\NotFound;
use App\Core\Domain\Order\Order;
use App\Infra\ORM\Order as ORMOrder;

final class OrderRepository implements OrderRepositoryContract
{
    public function save(Order $order): void
    {
        ORMOrder::query()->updateOrCreate(
            ['id' => $order->getId()],
            [
                'reference' => $order->getReference(),
                'customer_id' => $order->getCustomerId(),
                'amount_in_cents' => $order->getAmountInCents(),
                'status' => $order->getStatus()->value,
            ]
        );
    }

    public function getOneById(string $id): Order
    {
        $model = ORMOrder::query()->find($id);

        if (! $model instanceof ORMOrder) {
            throw NotFound::entityWithId('order', $id);
        }

        return Order::rebuild(
            id: $model->id,
            reference: $model->reference,
            customerId: $model->customer_id,
            amountInCents: $model->amount_in_cents,
            status: $model->status,
        );
    }

    public function getOneOrNullByReference(string $reference): ?Order
    {
        $model = ORMOrder::query()->where('reference', $reference)->first();

        if (! $model instanceof ORMOrder) {
            return null;
        }

        return Order::rebuild(
            id: $model->id,
            reference: $model->reference,
            customerId: $model->customer_id,
            amountInCents: $model->amount_in_cents,
            status: $model->status,
        );
    }
}
```

## 7. Rules for events

### 7.1 When to use events

Use events when a business change:

- triggers another step in the flow
- generates a secondary side effect
- needs to be decoupled from the main use case

### 7.2 How to model events

Events must:

- be small
- carry only what is necessary
- express a business fact that has already happened

Naming examples:

- `OrderCreated`
- `PaymentAuthorized`
- `TransferCompleted`

### 7.3 Subscribers

Subscribers must:

- react to a specific event
- have a single responsibility
- forward the flow to another handler or job

Subscribers must not:

- concentrate complex business rules
- become a second informal application service

### 7.4 Asynchrony

Use an async job when:

- there is an external call
- there is a need for retry
- the effect can be postponed

Do not use a queue by default.

The queue should be used only when it brings a clear benefit.

#### Snippet: event + subscriber

```php
<?php

declare(strict_types=1);

namespace App\Core\Domain\Event\Order;

use App\Core\Domain\Contracts\Event\Event;

final class Created implements Event
{
    public function __construct(private string $orderId)
    {
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Core\Domain\Event\Order;

use App\Core\Application\Order\SendReceipt;
use App\Core\Application\Order\SendReceiptHandler;
use App\Core\Domain\Contracts\Event\Subscriber;

final class CreatedSubscriber implements Subscriber
{
    public function __construct(private SendReceiptHandler $handler)
    {
    }

    public function listen(): array
    {
        return [Created::class];
    }

    public function process(object $event): void
    {
        assert($event instanceof Created);

        $this->handler->handle(new SendReceipt($event->getOrderId()));
    }
}
```

## 8. Rules for HTTP and boundaries

### 8.1 Controllers

Controllers must:

- receive a validated request
- build the command
- call the handler
- transform the result into an HTTP response

Controllers must not:

- implement business rules
- build database queries
- call external integrations directly

### 8.2 Requests

Requests must validate:

- required fields
- basic format
- primitive types

Business validation must not live in the request.

### 8.3 Responses

HTTP responses must be simple and consistent.

Domain errors must be converted into a standardized response by a dedicated exception handler.

#### Snippet: thin controller

```php
<?php

declare(strict_types=1);

namespace App\Infra\Http\Controller;

use App\Core\Application\Order\Create;
use App\Core\Application\Order\CreateHandler;
use App\Infra\Http\Request\Order\Create as CreateRequest;
use Hyperf\Di\Annotation\Inject;

final class OrderController extends AbstractController
{
    #[Inject]
    private CreateHandler $handler;

    public function create(CreateRequest $request)
    {
        $id = $this->handler->handle(
            new Create(
                customerId: $request->input('customer_id'),
                amountInCents: (int) $request->input('amount_in_cents'),
            )
        );

        return $this->response->json(['id' => $id])->withStatus(201);
    }
}
```

## 9. Rules for external integrations

Every external integration must have:

- a contract in the core
- a concrete adapter in the infrastructure
- a dedicated client per partner
- a dedicated response parser/handler
- its own tests

The application handler must depend on the contract, never on the concrete client.

### 9.1 Error mapping

External failures must be translated into exceptions coherent with the domain or with the application operation.

Do not propagate unnecessary technical details into the domain.

## 10. Rules for financial persistence and derived state

If the system has financial operations:

- preserve movement history
- avoid loose balance values without an audit trail
- consider the ledger the source of truth when traceability matters

If using a ledger:

- model credits and debits explicitly
- distinguish type and operation
- associate entries with relevant business events

In new projects, do not use `float` for money.

Use:

- integer cents
- or a `Money` value object

## 11. Rules for TDD and tests

### 11.1 Writing order

Every new feature must start with a test.

Recommended mandatory order:

1. domain test
2. handler test
3. minimal implementation
4. application integration test
5. HTTP test, if there is an endpoint
6. infrastructure adapter test

### 11.2 Unit tests

They must cover:

- entity rules
- factories
- handlers
- subscribers
- repository mappings
- external integrations in isolation

### 11.3 Integration tests

They must cover:

- flow between handlers
- collaboration between repositories and events
- HTTP input and output behavior

### 11.4 Doubles

Every important contract must have test doubles, such as:

- in-memory repository
- test publisher
- spied notifier
- fake authorizer

The doubles must be simple, predictable, and semantically close to the real contract.

#### Snippet: in-memory double

```php
<?php

declare(strict_types=1);

namespace HyperfTest\Doubles;

use App\Core\Domain\Contracts\OrderRepository;
use App\Core\Domain\Exceptions\NotFound;
use App\Core\Domain\Order\Order;

final class InMemoryOrderRepository implements OrderRepository
{
    /** @var array<string,Order> */
    private array $items = [];

    public function save(Order $order): void
    {
        $this->items[$order->getId()] = $order;
    }

    public function getOneById(string $id): Order
    {
        if (! isset($this->items[$id])) {
            throw NotFound::entityWithId('order', $id);
        }

        return $this->items[$id];
    }

    public function getOneOrNullByReference(string $reference): ?Order
    {
        foreach ($this->items as $item) {
            if ($item->getReference() === $reference) {
                return $item;
            }
        }

        return null;
    }
}
```

## 12. Naming conventions

### 12.1 Classes

Use business-oriented names:

- `Transfer`
- `TransferHandler`
- `TransferRepository`
- `TransferAuthorizer`
- `NotifyPayee`

### 12.2 Methods

Methods must reflect intent:

- `save`
- `getOneById`
- `authorize`
- `notify`
- `complete`
- `fail`

Avoid vague names:

- `processData`
- `handleStuff`
- `executeThing`

### 12.3 Tests

Name tests by behavior:

- `testCreatesPendingTransferAndPublishesEvent`
- `testFailsWhenAuthorizationDenied`
- `testDepositAppendsCreditEntryAndUpdatesBalance`

## 13. Operational checklist for each new feature

Before considering a feature done, confirm:

1. There is a dedicated command and handler.
2. The main rule is in the domain or clearly orchestrated in the application.
3. There is no business rule in the controller.
4. Every external dependency enters through a contract.
5. The repository returns a domain entity, not an ORM model.
6. Side effects were separated through an event or job when necessary.
7. There is a unit test for the rule.
8. There is a use case test.
9. There is an integration test appropriate for the flow.
10. The naming is coherent with the ubiquitous language.

## 14. What to avoid

Do not:

- couple the domain to the framework
- put rules in the controller
- let handlers depend on concrete implementations
- use generic exceptions for everything
- store money as `float`
- create overly generic repositories
- use events unnecessarily
- create jobs that hide the main business rule
- skip domain tests and test only HTTP

## 15. Minimum implementation template

### 15.1 New use case

1. Create the use case test.
2. Create or adjust the domain test.
3. Create the command.
4. Create the handler.
5. Define the required contracts.
6. Implement the rule in the domain.
7. Implement the concrete persistence.
8. Publish the event, if there is a later step.
9. Create the subscriber or job, if necessary.
10. Create the integration test.

#### Snippet: handler test

```php
<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Unit\Core\Application\Order;

use App\Core\Application\Order\Create;
use App\Core\Application\Order\CreateHandler;
use App\Core\Domain\Contracts\Event\Publisher;
use HyperfTest\Doubles\InMemoryOrderRepository;
use Mockery;
use PHPUnit\Framework\TestCase;

final class CreateHandlerTest extends TestCase
{
    public function testCreatesOrderAndPublishesEvent(): void
    {
        $repository = new InMemoryOrderRepository();
        $publisher = Mockery::mock(Publisher::class);

        $publisher->shouldReceive('publish')->once();

        $handler = new CreateHandler($repository, $publisher);
        $id = $handler->handle(new Create('customer-1', 1500));

        $this->assertNotEmpty($id);
        $this->assertNotNull($repository->getOneById($id));
    }
}
```

### 15.2 New module

1. Create a folder in `app/Core/Domain/<Module>`.
2. Create a folder in `app/Core/Application/<Module>`.
3. Create contracts in `app/Core/Domain/Contracts`.
4. Create a persistence adapter in `app/Infra/Persistence`.
5. Create an HTTP adapter in `app/Infra/Http`.
6. Create the corresponding doubles in `test/Doubles`.
7. Define the module unit and integration tests.

## 16. Final consistency rule

If a new implementation violates any of these conditions, it must be reviewed:

- the domain started knowing about the framework
- the use case became dependent on concrete technology
- the controller gained business rules
- the repository stopped rebuilding the domain
- the feature cannot be tested without real infrastructure
- the ubiquitous language became inconsistent

If that happens, the implementation is outside the standard and must not be considered complete.
