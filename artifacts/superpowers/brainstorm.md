# Brainstorm: Testing Strategy for laravel-whatsapp

## Goal
Establish a robust testing suite for the package, covering unit tests for core logic (e.g., token parsing, normalization) and integration tests for Laravel-specific components (models, migrations, webhooks, services).

## Constraints
- **Package Context**: Tests must run in isolation without a full Laravel app, requiring `orchestra/testbench`.
- **API Mocking**: Must mock Meta Graph API responses to avoid real network calls.
- **Environment**: Support PHP 8.2+ and Laravel 10/11/12 as per `composer.json`.

## Known context
- No existing `tests` directory.
- `composer.json` is missing `require-dev` for testing tools like `phpunit` and `testbench`.
- Core features to test:
    - `WhatsAppService`: Sending messages, token creation, token consumption.
    - `WebhookController`: Verification, status updates, inbound message handling (including tokens).
    - `Models`: Scopes and attribute casting.
    - `Migrations`: Ensuring schema loads correctly.

## Risks
- **External Dependencies**: Dependence on `Http` facade requires careful mocking.
- **WABA mapping**: Testing multi-account logic requires setting up multiple `WhatsappAccount` records in a test database (SQLite in-memory).
- **Mixed Message Parsing**: Edge cases in `consumeToken` regex/contains logic need thorough coverage.

## Options (2–4)

### 1. Minimal PHPUnit Setup
Just add `phpunit` and some unit tests for non-Laravel logic.
- **Pros**: Lightweight.
- **Cons**: Cannot easily test models, migrations, or service providers.

### 2. Orchestra Testbench (Recommended)
Industry standard for Laravel package testing.
- **Pros**: Allows testing full Laravel integration (Migrations, Facades, Service Providers).
- **Cons**: Slightly higher setup complexity (requires a `TestCase.php` that extends Testbench).

### 3. Pest Framework
A modern, expressive testing framework built on top of PHPUnit.
- **Pros**: Very readable, easy to use higher-order tests.
- **Cons**: Another dependency; user might prefer standard PHPUnit.

## Recommendation
**Option 2 (Orchestra Testbench with PHPUnit)** is recommended. It provides the best balance of power and compatibility for a Laravel package, allowing us to test exactly how the package interacts with a Laravel application.

## Acceptance criteria
- [ ] `composer.json` updated with `phpunit`, `orchestra/testbench`, and `mockery`.
- [ ] `tests` directory created with `TestCase.php` configuration.
- [ ] **Unit Tests**:
    - `NormalizesPhoneNumbers` trait logic.
    - Token generation formats (`alphanumeric`, `numeric`, `uuid`).
- [ ] **Integration Tests**:
    - `WhatsappToken` model scopes (active/expired).
    - `WhatsAppService::consumeToken` with mixed messages.
    - `WebhookController` handles Meta payloads and triggers token verification.
- [ ] GitHub Actions (optional but recommended) setup for CI.
