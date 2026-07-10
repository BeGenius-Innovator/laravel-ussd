# Laravel USSD

[![Latest Version](https://img.shields.io/packagist/v/begenius/laravel-ussd.svg?style=flat-square)](https://packagist.org/packages/begenius/laravel-ussd)
[![MIT License](https://img.shields.io/packagist/l/begenius/laravel-ussd.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/begenius/laravel-ussd.svg?style=flat-square)](https://packagist.org/packages/begenius/laravel-ussd)

Build production-grade USSD applications in Laravel.

**laravel-ussd** is a framework-agnostic USSD engine that lets you create telecom-grade USSD services with a clean, declarative API. It supports multiple gateways (Orange, Moov, Africa's Talking, Infobip, etc.) through a driver-based architecture.

---

## Table of Contents

- [Presentation](#presentation)
- [Installation](#installation)
- [Configuration](#configuration)
- [Quick Start](#quick-start)
- [Menus](#menus)
- [Flows](#flows)
- [Actions](#actions)
- [Sessions](#sessions)
- [Drivers](#drivers)
- [Simulator](#simulator)
- [Testing](#testing)
- [Architecture](#architecture)
- [Contributing](#contributing)
- [Roadmap](#roadmap)

---

## Presentation

### What is USSD?

USSD (Unstructured Supplementary Service Data) is a protocol used by GSM phones to communicate with service providers. It's the technology behind `*123#` codes — used for mobile money, balance checks, and telecom services across Africa and beyond.

### What this package does

This package provides a complete framework for building USSD services:

- **Menu system** — Declarative, tree-based navigation
- **Flow engine** — Multi-step workflows with state machine
- **Session management** — Persistent state across USSD requests
- **Gateway abstraction** — Works with any telecom provider
- **Validation** — Input validation per flow step
- **Logging** — Request/response logging
- **Simulator** — Web-based testing tool
- **Error handling** — Graceful error recovery

### Architecture overview

```
                    ┌──────────────────┐
                    │     Telephone    │
                    └────────┬─────────┘
                             │ USSD
                    ┌────────▼─────────┐
                    │  USSD Gateway    │
                    │(Orange, Moov, AT)│
                    └────────┬─────────┘
                             │ HTTP POST
                    ┌────────▼─────────┐
                    │  UssdController  │
                    └────────┬─────────┘
                             │
                    ┌────────▼─────────┐
                    │   UssdEngine     │
                    │  (Orchestrator)  │
                    └──┬────┬────┬─────┘
                       │    │    │
              ┌────────┘    │    └────────┐
              ▼             ▼             ▼
        ┌─────────┐ ┌──────────┐ ┌──────────┐
        │ Session │ │   Menu   │ │   Flow   │
        │ Manager │ │  Manager │ │  Engine  │
        └─────────┘ └──────────┘ └──────────┘
```

---

## Installation

```bash
composer require begenius/laravel-ussd
```

Laravel will auto-discover the service provider. If you're using Laravel < 5.5, add this to `config/app.php`:

```php
'providers' => [
    BeGenius\Ussd\UssdServiceProvider::class,
],
```

### Publish configuration

```bash
php artisan vendor:publish --tag=ussd-config
```

### Run migrations

```bash
php artisan vendor:publish --tag=ussd-migrations
php artisan migrate
```

---

## Configuration

Configure the package in `config/ussd.php` or via environment variables:

```env
USSD_DRIVER=default
USSD_SESSION_DRIVER=database
USSD_SESSION_LIFETIME=2
USSD_ROUTES_PREFIX=ussd
USSD_SIMULATOR_ENABLED=false
USSD_LOGGING_ENABLED=true
```

| Option | Default | Description |
|--------|---------|-------------|
| `default_driver` | `default` | USSD gateway driver |
| `session_driver` | `database` | Session storage driver |
| `session_lifetime` | `2` | Session timeout (minutes) |
| `session_table` | `ussd_sessions` | Database table name |
| `routes_prefix` | `ussd` | URL prefix for USSD routes |
| `default_menu` | `welcome` | Initial menu name |
| `max_input_length` | `182` | Max input characters |
| `simulator_enabled` | `false` | Enable web simulator |

---

## Quick Start

### 1. Define menus in a Service Provider

```php
<?php

namespace App\Providers;

use BeGenius\Ussd\Facades\Ussd;
use Illuminate\Support\ServiceProvider;

class UssdServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Ussd::menu('welcome')
            ->title("Welcome to MyService")
            ->option('1', 'Check Balance')
            ->option('2', 'Transfer Money')
            ->option('3', 'Help', nextMenu: 'help');
    }
}
```

### 2. Configure your gateway

Set your USSD gateway to send POST requests to:

```
https://your-domain.com/ussd/callback
```

### 3. The gateway sends payloads like

```json
{
    "sessionId": "ABC123",
    "phoneNumber": "22670000000",
    "network": "ORANGE",
    "text": "1"
}
```

Your application handles the rest.

---

## Menus

Menus are the building blocks of your USSD application. They display options and handle navigation.

### API

```php
use BeGenius\Ussd\Facades\Ussd;

Ussd::menu('main')
    ->title("Main Menu")
    ->option('1', 'Balance', BalanceAction::class)
    ->option('2', 'Transfer', TransferFlow::class)
    ->option('3', 'Settings', nextMenu: 'settings')
    ->option('0', 'Exit');
```

### Sub-menus

```php
Ussd::menu('settings')
    ->title("Settings")
    ->option('1', 'Language', LanguageAction::class)
    ->option('0', 'Back', nextMenu: 'main');
```

### Navigation flow

- **Action**: An action class (invokable) executes business logic
- **Flow**: A multi-step workflow
- **nextMenu**: Navigate to another menu
- **No action/no nextMenu**: Re-renders the current menu

### Rendering

Menus are auto-rendered as `CON` responses with numbered options:

```
CON Main Menu
1. Balance
2. Transfer
3. Settings
0. Exit
```

---

## Flows

Flows handle multi-step transactions like money transfers.

### Creating a Flow

```php
namespace App\Ussd\Flows;

use BeGenius\Ussd\Core\UssdContext;
use BeGenius\Ussd\Flows\Flow;
use BeGenius\Ussd\Flows\Step;
use BeGenius\Ussd\Flows\StepResult;
use BeGenius\Ussd\Responses\UssdResponse;

class TransferFlow extends Flow
{
    public function __construct()
    {
        parent::__construct('transfer', 'ask_recipient');

        $this->addStep(new class extends Step {
            public function name(): string
            {
                return 'ask_recipient';
            }

            public function handle(UssdContext $context): StepResult
            {
                return StepResult::next(
                    'ask_amount',
                    UssdResponse::continue('Enter recipient phone:')
                );
            }
        });

        $this->addStep(new class extends Step {
            public function name(): string
            {
                return 'ask_amount';
            }

            public function validate(UssdContext $context): ?string
            {
                $amount = $context->input();
                if (!is_numeric($amount) || $amount <= 0) {
                    return 'Invalid amount. Enter a positive number.';
                }
                return null;
            }

            public function handle(UssdContext $context): StepResult
            {
                $context->session()->set('amount', $context->input());

                return StepResult::next(
                    'confirm',
                    UssdResponse::continue(
                        "Confirm transfer:\n".
                        "To: {$context->session()->get('recipient')}\n".
                        "Amount: {$context->input()} FCFA\n".
                        "1. Confirm\n".
                        "2. Cancel"
                    )
                );
            }
        });

        $this->addStep(new class extends Step {
            public function name(): string
            {
                return 'confirm';
            }

            public function handle(UssdContext $context): StepResult
            {
                if ($context->input() === '1') {
                    // Process the transfer...
                    return StepResult::complete(
                        UssdResponse::end('Transfer successful!')
                    );
                }

                return StepResult::complete(
                    UssdResponse::end('Transfer cancelled.')
                );
            }
        });
    }
}
```

### Register a flow in a menu

```php
Ussd::menu('main')
    ->option('2', 'Transfer Money', TransferFlow::class);
```

Or register it independently:

```php
$flow = new TransferFlow();
Ussd::registerFlow($flow);
```

---

## Actions

Actions are invokable classes that handle a single menu selection.

```php
namespace App\Ussd\Actions;

use BeGenius\Ussd\Core\UssdContext;
use BeGenius\Ussd\Responses\UssdResponse;

class BalanceAction
{
    public function __invoke(UssdContext $context): UssdResponse
    {
        $balance = $this->getBalance($context->session()->phoneNumber());

        return UssdResponse::end("Your balance is: {$balance} FCFA");
    }

    private function getBalance(string $phoneNumber): float
    {
        // Query your database or API
        return 15000.00;
    }
}
```

You can also use closures:

```php
Ussd::menu('main')
    ->option('1', 'Balance', function (UssdContext $context) {
        return UssdResponse::end('Your balance is 5000 FCFA');
    });
```

---

## Sessions

Sessions persist user state across USSD requests. The package supports database and in-memory (array) drivers.

### Session data

Store temporary data during flows:

```php
$context->session()->set('recipient', '22671234567');
$context->session()->set('amount', '5000');

$recipient = $context->session()->get('recipient');
$hasAmount  = $context->session()->has('amount');
$context->session()->forget('amount');
```

### Session lifecycle

1. **Created** — When a user dials the service code
2. **Active** — During menu navigation and flow execution
3. **Expired** — After `session_lifetime` minutes of inactivity
4. **Destroyed** — After successful END response

---

## Drivers

### USSD Gateway Drivers

Create a driver for your specific gateway:

```php
namespace App\Ussd\Drivers;

use BeGenius\Ussd\Contracts\UssdDriver;
use BeGenius\Ussd\Http\Requests\UssdRequest;
use Illuminate\Http\Request;

class OrangeDriver implements UssdDriver
{
    public function parseRequest(Request $request): UssdRequest
    {
        return new UssdRequest(
            sessionId:   $request->input('sessionId'),
            phoneNumber: $request->input('msisdn'),
            network:     'ORANGE',
            text:        $request->input('ussdText', ''),
            raw:         $request->all(),
            serviceCode: $request->input('serviceCode'),
        );
    }
}
```

Register your driver in the service provider:

```php
// In UssdServiceProvider registration
$this->app->bind(UssdDriverContract::class, function ($app) {
    return new OrangeDriver();
});
```

### Session Drivers

Built-in session drivers:

- **database** — MySQL/PostgreSQL storage (production)
- **array** — In-memory (testing only)

Create a Redis driver:

```php
use BeGenius\Ussd\Contracts\SessionDriver;
use BeGenius\Ussd\Core\UssdSession;
use Illuminate\Support\Facades\Redis;

class RedisSessionDriver implements SessionDriver
{
    public function find(string $sessionId): ?UssdSession { /* ... */ }
    public function save(UssdSession $session): void      { /* ... */ }
    public function delete(string $sessionId): void        { /* ... */ }
    public function purgeExpired(int $lifetime): int       { /* ... */ }
}
```

---

## Simulator

Test your USSD application without a real gateway:

```env
USSD_SIMULATOR_ENABLED=true
```

Open `http://localhost:8000/ussd/simulator` in your browser.

```
┌─────────────────────┐
│   USSD Simulator    │
│                     │
│  CON Welcome        │
│  1. Balance         │
│  2. Transfer        │
│                     │
├─────────────────────┤
│ [Input: 1     ] [→] │
│                     │
│ Session: abc123     │
└─────────────────────┘
```

> ⚠️ Disable in production: `USSD_SIMULATOR_ENABLED=false`

---

## Testing

```bash
composer test
```

The package uses PHPUnit with Orchestra Testbench.

```php
public function it_creates_a_continue_response(): void
{
    $response = UssdResponse::continue("Welcome\n1. Balance");

    $this->assertTrue($response->isContinue());
    $this->assertEquals('CON', $response->type());
    $this->assertEquals("CON Welcome\n1. Balance\n", $response->toString());
}
```

---

## Architecture

```
src/
├── UssdServiceProvider.php        # Laravel service provider
├── Facades/
│   └── Ussd.php                   # Facade for UssdEngine
├── Config/
│   └── ussd.php                   # Configuration file
├── Core/
│   ├── UssdEngine.php             # Main orchestrator
│   ├── UssdSession.php            # Session value object
│   └── UssdContext.php            # Request context
├── Http/
│   ├── Controllers/
│   │   ├── UssdController.php     # Gateway callback controller
│   │   └── SimulatorController.php # Web simulator
│   └── Requests/
│       └── UssdRequest.php        # Parsed USSD request
├── Responses/
│   └── UssdResponse.php           # CON/END response builder
├── Menus/
│   ├── Menu.php                   # Menu definition
│   └── MenuOption.php             # Single menu option
├── Flows/
│   ├── Flow.php                   # Multi-step workflow
│   ├── Step.php                   # Single flow step
│   └── StepResult.php             # Step execution result
├── Contracts/
│   ├── UssdDriver.php             # Gateway driver interface
│   └── SessionDriver.php          # Session storage interface
├── Drivers/
│   ├── DefaultUssdDriver.php      # Default gateway driver
│   └── Session/
│       ├── DatabaseSessionDriver.php # DB session storage
│       └── ArraySessionDriver.php    # In-memory session
├── Services/
│   ├── SessionManager.php         # Session lifecycle
│   └── MenuManager.php            # Menu registry
├── Exceptions/
│   ├── UssdException.php          # Base exception
│   ├── InvalidMenuException.php   # Menu not found
│   └── SessionExpiredException.php # Session timeout
└── Console/                       # Artisan commands (future)
```

### Key design patterns

| Pattern | Usage |
|---------|-------|
| **Orchestration** | UssdEngine coordinates components |
| **Builder** | Fluent menu/flow API |
| **Registry** | MenuManager stores menus by name |
| **State** | Flow steps as state machine |
| **Strategy** | Drivers interchangeable via interface |
| **Context** | UssdContext carries state through pipeline |
| **Value Object** | UssdRequest, UssdResponse, UssdSession |

---

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/my-feature`
3. Commit changes: `git commit -am 'Add my feature'`
4. Push: `git push origin feature/my-feature`
5. Open a Pull Request

Please follow PSR-12 coding standards and include tests.

---

## Roadmap

### v1.0 (current)

- [x] Menu system with fluent API
- [x] Multi-step flow engine (state machine)
- [x] Session management (database + array drivers)
- [x] Gateway driver abstraction
- [x] USSD simulator
- [x] Request/response logging
- [x] Error handling
- [x] PHPUnit test suite

### v1.1

- [ ] Redis session driver
- [ ] Orange official driver
- [ ] Africa's Talking driver
- [ ] Moov Africa driver
- [ ] Artisan command: `ussd:list` (list all menus/flows)
- [ ] Rate limiting

### v1.2

- [ ] Visual flow builder
- [ ] Analytics dashboard
- [ ] Scheduled session cleanup
- [ ] Multi-language support
- [ ] Broadcast messages

### v2.0

- [ ] Visual flow designer UI
- [ ] Webhook integration
- [ ] REST API for session management
- [ ] Push USSD (MT sessions)
- [ ] Laravel Livewire component

---

## License

**laravel-ussd** is open-source software licensed under the [MIT license](LICENSE).

---

Built with ❤️ by [BeGenius](https://github.com/begenius)
