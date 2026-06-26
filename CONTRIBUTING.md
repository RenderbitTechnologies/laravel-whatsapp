# Contributing to Laravel WhatsApp

Thanks for your interest in contributing! This guide covers everything you need to get started.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Project Structure](#project-structure)
- [Making Changes](#making-changes)
- [Testing](#testing)
- [Coding Standards](#coding-standards)
- [Commit Messages](#commit-messages)
- [Pull Request Process](#pull-request-process)
- [Reporting Bugs](#reporting-bugs)
- [Requesting Features](#requesting-features)

## Code of Conduct

Be respectful and constructive in all interactions. We're here to build something useful together.

## Getting Started

1. **Fork** the repository on GitHub
2. **Clone** your fork locally:

```bash
git clone https://github.com/<your-username>/laravel-whatsapp.git
cd laravel-whatsapp
```

3. **Add upstream remote**:

```bash
git remote add upstream https://github.com/RenderbitTechnologies/laravel-whatsapp.git
```

## Development Setup

### Requirements

| Dependency | Version |
|---|---|
| PHP | >= 8.1 |
| Composer | 2.x |
| Laravel (for integration tests) | 10.x or 11.x |

### Install Dependencies

```bash
composer install
```

### Environment

No `.env` file is needed for development — tests mock all API calls and cache interactions.

## Project Structure

```
src/
├── Constants/
│   └── ErrorCodes.php          # API error code mappings
├── Facades/
│   └── Whatsapp.php            # Laravel facade
├── Http/
│   └── Controllers/
│       └── WhatsAppDLRController.php  # DLR webhook handler
├── TokenManager.php            # Token lifecycle (cache/generate/refresh)
├── WhatsappClient.php          # Main API client
└── WhatsappServiceProvider.php # Laravel service provider

tests/
├── TestCase.php                # Base test (Mockery)
├── LaravelTestCase.php         # Base test (Orchestra Testbench)
├── ErrorCodesTest.php
├── TokenManagerTest.php
├── WhatsappClientTest.php
├── WhatsAppDLRControllerTest.php
├── WhatsappFacadeTest.php
└── WhatsappServiceProviderTest.php
```

## Making Changes

### Branching

Create a feature branch from `main`:

```bash
git checkout main
git pull upstream main
git checkout -b feat/your-feature-name
```

Use descriptive branch names:

| Prefix | Use For |
|---|---|
| `feat/` | New features |
| `fix/` | Bug fixes |
| `docs/` | Documentation only |
| `refactor/` | Code restructuring |
| `test/` | Test additions or fixes |
| `chore/` | CI, build, maintenance |

### PSR Compliance

This package follows PSR standards:

- **PSR-4** — Autoloading (`Renderbit\LaravelWhatsapp\` maps to `src/`)
- **PSR-3** — Logging via `Psr\Log\LoggerInterface`
- **PSR-16** — Caching via `Psr\Cache\CacheInterface`

## Testing

### Run the Full Suite

```bash
vendor/bin/phpunit
```

### Run a Specific Test File

```bash
vendor/bin/phpunit tests/WhatsappClientTest.php
```

### Run a Single Test Method

```bash
vendor/bin/phpunit --filter=test_send_message_success
```

### Coverage

Tests are run across a matrix of PHP 8.1–8.4 and Laravel 10–11 via GitHub Actions. All tests must pass before merging.

**Before submitting a PR**, ensure:

1. All existing tests still pass
2. New code is covered by tests
3. No regressions in error handling or token management

## Coding Standards

### General

- **PHP >= 8.1** — use typed properties, enums, and union types where appropriate
- Keep methods focused and short
- Use descriptive variable/method names

### Laravel Conventions

- Service provider publishes config and routes under the `whatsapp-config` and `whatsapp-routes` tags
- Facade resolves via the service container
- Use `illuminate/support` helpers sparingly — keep the package framework-agnostic

### Documentation

- Add PHPDoc blocks to public methods
- Update `README.md` if adding/changing user-facing functionality
- Update `config/whatsapp.php` comments when adding configuration keys

## Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(scope): <description>

[optional body]
```

| Type | Purpose |
|---|---|
| `feat` | New feature |
| `fix` | Bug fix |
| `docs` | Documentation only |
| `refactor` | Code change that neither fixes a bug nor adds a feature |
| `test` | Adding or updating tests |
| `chore` | CI, build, or maintenance |

**Examples:**

```
feat(client): add interactive message support
fix(token): handle cache driver connection failures
docs: update DLR webhook configuration section
test(client): cover retry logic on 401 responses
chore(ci): add PHP 8.4 to test matrix
```

## Pull Request Process

1. **Ensure tests pass** — `vendor/bin/phpunit` with no failures
2. **Push your branch**:

```bash
git push origin feat/your-feature-name
```

3. **Open a PR** against `main` using the provided template
4. **Fill out the PR template** completely — describe what changed and why
5. **Link related issues** — use `Closes #...` or `Refs #...`
6. **Respond to review feedback** — push additional commits as needed

### PR Review Criteria

PRs are reviewed for:

- ✅ All CI checks passing (PHP 8.1–8.4 × Laravel 10–11 matrix)
- ✅ Tests for new/changed functionality
- ✅ No regressions in existing behavior
- ✅ PSR-4 autoloading and PSR compliance
- ✅ Clear, descriptive commit messages
- ✅ Updated documentation (if applicable)

## Reporting Bugs

Use the [Bug Report template](https://github.com/RenderbitTechnologies/laravel-whatsapp/issues/new?template=bug_report.yml) and include:

- Steps to reproduce
- Expected vs actual behavior
- PHP version, Laravel version, and package version
- Relevant logs or stack traces

## Requesting Features

Use the [Feature Request template](https://github.com/RenderbitTechnologies/laravel-whatsapp/issues/new?template=feature_request.yml) and include:

- Motivation and use case
- Proposed solution with code examples
- Acceptance criteria

## License

By contributing, you agree that your contributions will be licensed under the [MIT License](LICENSE).
