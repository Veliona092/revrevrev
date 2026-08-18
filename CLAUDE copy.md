# Laravel Boost Guidelines

You are an expert Laravel developer working on this specific project.

## Project Context
- Laravel 12 (upgraded from Laravel 10 — **keep the existing Laravel 10 structure**)
- PHP 8.5
- Livewire v4
- PHPUnit for testing (convert any Pest tests to PHPUnit)
- Laravel Pint for code formatting
- Using Laravel Boost + MCP tools

## Core Priorities (Always follow these first)
- Follow existing code style and patterns in **sibling files**
- Use descriptive names (`isRegisteredForDiscounts`, not `discount()`)
- Reuse existing components, actions, and patterns when possible
- Be concise in explanations

## Laravel Best Practices
- Use Form Requests for validation (never inline validation in controllers)
- Prefer Eloquent relationships + eager loading over raw queries
- Use Action classes for complex business logic
- Always create factories + seeders when making new models
- Use named routes and the `route()` helper
- Put environment-specific values **only** in config files (`config()` — never `env()` outside config)
- Always use `php artisan make:...` commands with `--no-interaction`

## PHP & Code Style
- Use constructor property promotion
- Always add explicit return types and parameter type hints
- Prefer PHPDoc blocks over inline comments
- Use curly braces even for single-line statements
- Run `vendor/bin/pint --dirty --format agent` after every code change

## Laravel 12 Specifics
- When modifying columns in migrations, re-specify **all** previous attributes
- Prefer `casts()` method over `$casts` property on models (follow existing models)

## Testing
- Write PHPUnit tests only
- Use model factories in tests
- Cover happy path, failure cases, and edge cases
- After updating a test, run it individually: `php artisan test --filter=TestName`

## Livewire
- Keep state on the server
- Validate and authorize inside component actions
- Prefer Alpine.js for simple client-side interactions

## Boost & Tools
- Use Boost MCP tools heavily (`database-schema`, `search-docs`, `route:list`, etc.)
- **Always use `search-docs` tool** before making decisions on Laravel or package features

## General
- Do not change dependencies or project structure without explicit approval
- If UI changes don't appear, remind the user to run `npm run dev` or `npm run build`
- When in doubt, check existing similar files first