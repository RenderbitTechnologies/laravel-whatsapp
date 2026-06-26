# GitHub Copilot Instructions

## Priority Guidelines

When generating code for this repository:

1. **Version Compatibility**: Always detect and respect the exact versions of languages, frameworks, and libraries used in this project
2. **Context Files**: Prioritize patterns and standards defined in the .github/copilot directory
3. **Codebase Patterns**: When context files don't provide specific guidance, scan the codebase for established patterns
4. **Architectural Consistency**: Maintain the Layered architectural style and established boundaries
5. **Code Quality**: Prioritize maintainability, security, and testability in all generated code

## Technology Version Detection

Before generating code, scan the codebase to identify:

1. **Language Versions**: Detect the exact versions of programming languages in use
   - This project requires PHP >= 8.1
   - Use PHP 8.1+ features (typed properties, named arguments, union types, attributes)
   - Never use language features beyond PHP 8.1

2. **Framework Versions**: Identify the exact versions of all frameworks
   - Laravel/Illuminate: ^10.0 || ^11.0
   - Guzzle HTTP Client: ^7.9
   - PSR Log: ^1.1 || ^2.0 || ^3.0
   - PSR SimpleCache: ^1.0 || ^2.0 || ^3.0
   - PHPUnit: ^11.0
   - Mockery: ^1.6
   - Orchestra Testbench: ^9.0

3. **Library Versions**: Note the exact versions of key libraries and dependencies
   - Generate code compatible with these specific versions
   - Never use APIs or features not available in the detected versions

## Context Files

Prioritize the following files in .github/copilot directory (if they exist):

- **architecture.md**: System architecture guidelines
- **tech-stack.md**: Technology versions and framework details
- **coding-standards.md**: Code style and formatting standards
- **folder-structure.md**: Project organization guidelines
- **exemplars.md**: Exemplary code patterns to follow

## Codebase Scanning Instructions

When context files don't provide specific guidance:

1. Identify similar files to the one being modified or created
2. Analyze patterns for:
   - Naming conventions (PascalCase for classes, camelCase for methods, snake_case for config keys)
   - Code organization (namespace structure: `Renderbit\LaravelWhatsapp\*`)
   - Error handling (try-catch with logger error calls, returning `['success' => false, 'message' => '...']` arrays)
   - Logging approaches (PSR-3 LoggerInterface, `$this->logger->info()`, `$this->logger->error()`)
   - Documentation style (PHPDoc for public methods with `@param` and brief description)
   - Testing patterns (PHPUnit 11 attributes `#[Test]`, Mockery for mocking, `setUp()`/`tearDown()` lifecycle)
   
3. Follow the most consistent patterns found in the codebase
4. When conflicting patterns exist, prioritize patterns in newer files or files with higher test coverage
5. Never introduce patterns not found in the existing codebase

## Code Quality Standards

### Maintainability
- Write self-documenting code with clear naming
- Follow the naming and organization conventions evident in the codebase:
  - Classes: PascalCase (`WhatsappClient`, `TokenManager`, `WhatsAppDLRController`)
  - Methods: camelCase (`sendMessage`, `getToken`, `refreshToken`)
  - Config keys: snake_case (`api_base_url`, `api_key`, `whatsapp_business_number`)
  - Constants: UPPER_SNAKE_CASE (`MAP`)
  - Test methods: snake_case with `it_` prefix (`it_sends_message_successfully`, `it_handles_api_error_code_in_response`)
- Follow established patterns for consistency
- Keep functions focused on single responsibilities
- Limit function complexity and length to match existing patterns

### Security
- Follow existing patterns for input validation
- Apply the same sanitization techniques used in the codebase
- Use parameterized queries matching existing patterns
- Follow established authentication and authorization patterns
- Handle sensitive data according to existing patterns
- Never expose API keys, tokens, or credentials in logs or responses

### Testability
- Follow established patterns for testable code
- Match dependency injection approaches used in the codebase (constructor injection for LoggerInterface and CacheInterface)
- Apply the same patterns for managing dependencies
- Follow established mocking and test double patterns (Mockery for interfaces, Guzzle MockHandler for HTTP)
- Match the testing style used in existing tests

## Documentation Requirements

- Follow the exact documentation format found in the codebase
- Match the PHPDoc style and completeness of existing comments
- Document parameters, returns, and exceptions in the same style
- Follow existing patterns for usage examples
- Match class-level documentation style and content

## Testing Approach

### Unit Testing
- Match the exact structure and style of existing unit tests
- Follow the same naming conventions for test classes and methods
- Use the same assertion patterns found in existing tests (`$this->assertTrue`, `$this->assertFalse`, `$this->assertEquals`, `$this->assertInstanceOf`)
- Apply the same mocking approach used in the codebase (Mockery for PSR interfaces, Guzzle MockHandler for HTTP clients)
- Follow existing patterns for test isolation
- Use `#[Test]` PHP attributes (not `@test` annotations)

### Integration Testing
- Follow the same integration test patterns found in the codebase
- Match existing patterns for test data setup and teardown
- Use Orchestra Testbench for Laravel integration testing
- Follow existing patterns for testing component interactions
- Match `LaravelTestCase` patterns for tests requiring Laravel service container

### Test Structure Patterns
- Extend `TestCase` for unit tests (base PHPUnit + Mockery)
- Extend `LaravelTestCase` for integration tests requiring Laravel services
- Use `setUp()` to initialize mocks and config from `$this->defaultConfig`
- Use reflection to replace private/protected dependencies in tests
- Use `MockHandler` and `HandlerStack` for HTTP client mocking

## PHP / Laravel Guidelines

### PHP Guidelines
- Use PHP 8.1+ features: typed properties, named arguments, union types, attributes, constructor promotion
- Follow PSR-12 coding standards
- Use strict typing where appropriate
- Follow the namespace structure: `Renderbit\LaravelWhatsapp\*`
- Use type declarations for parameters and return types
- Follow existing patterns for property visibility (use `protected` for class properties)

### Laravel Guidelines
- Follow Laravel service provider patterns exactly as seen in `WhatsappServiceProvider`
- Use singleton binding for service registration
- Use `config()` helper for configuration access
- Use Laravel facades following the pattern in `Whatsapp.php`
- Use `response()->json()` for JSON responses in controllers
- Use Laravel route definitions following `routes/api.php` patterns
- Follow existing patterns for route registration and controller organization

### HTTP Client Guidelines
- Use Guzzle HTTP Client ^7.9 for all HTTP requests
- Follow the exact request/response handling patterns in `WhatsappClient`
- Use `json_encode()`/`json_decode()` for JSON serialization
- Handle `RequestException` with proper status code checking
- Follow existing patterns for request headers and authentication

### Error Handling Guidelines
- Return `['success' => bool, 'message' => string]` arrays from client methods
- Return `['error' => '...']` arrays for request failures
- Use `ErrorCodes::MAP` for API error code resolution
- Log errors using PSR-3 `LoggerInterface`
- Follow existing try-catch patterns with logging

## Version Control Guidelines

- Follow Semantic Versioning patterns as applied in the codebase
- Match existing patterns for documenting breaking changes
- Follow the same approach for deprecation notices
- Use conventional commit messages

## General Best Practices

- Follow naming conventions exactly as they appear in existing code
- Match code organization patterns from similar files
- Apply error handling consistent with existing patterns
- Follow the same approach to testing as seen in the codebase
- Match logging patterns from existing code
- Use the same approach to configuration as seen in the codebase

## Project-Specific Guidance

- Scan the codebase thoroughly before generating any code
- Respect existing architectural boundaries without exception
- Match the style and patterns of surrounding code
- When in doubt, prioritize consistency with existing code over external best practices

## Architecture Overview

This is a Laravel package (`renderbit/laravel-whatsapp`) providing WhatsApp messaging via the Renderbit API. Key architectural patterns:

- **Service Provider**: `WhatsappServiceProvider` registers the client as a singleton in the Laravel container
- **Facade**: `Whatsapp` facade provides static access to `WhatsappClient`
- **Client Pattern**: `WhatsappClient` handles API communication, token management is delegated to `TokenManager`
- **Token Management**: `TokenManager` handles token generation, caching, and refresh using PSR SimpleCache
- **Error Handling**: Centralized error codes in `ErrorCodes` constant map
- **HTTP Layer**: DLR (Delivery Receipt) webhook via `WhatsAppDLRController`
- **Testing**: Two test base classes - `TestCase` (unit) and `LaravelTestCase` (integration via Orchestra Testbench)
