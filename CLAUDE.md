# Neon Project Context & Best Practices

This document contains important context and coding standards for the Neon project - a Laravel application for managing Discord servers with billing/licensing features.

## Project Overview
- **Stack**: Laravel 12, Inertia.js, React, TypeScript, Tailwind CSS
- **Database**: MySQL/PostgreSQL with Eloquent ORM
- **Authentication**: Discord OAuth via Socialite
- **Billing**: Stripe integration with Laravel Cashier
- **Admin Panel**: Laravel Nova
- **Real-time**: Laravel Reverb
- **Monitoring**: Laravel Pulse

## Coding Standards & Preferences

### General Principles
1. **Apply DRY and SOLID principles** - When in conflict, prefer DRY (Don't Repeat Yourself) over strict SOLID adherence
2. **Pragmatic over dogmatic** - Write clean, maintainable code that solves the problem efficiently
3. **Extract common logic** - If code is repeated 3+ times, extract it to a method, trait, or service

### PHP/Laravel
1. **Use `final` classes appropriately** - Final classes are allowed and encouraged where inheritance isn't needed. Use them for service classes, DTOs, and other classes that shouldn't be extended
2. **No extending base Controller** - Controllers should be standalone classes
3. **No inline imports** - Always use proper `use` statements at the top
4. **Model::unguard() in all environments** - Mass assignment protection disabled globally
5. **Naming conventions**:
   - Variables: `camelCase`
   - Routes: `kebab-case`
   - Database columns: `snake_case`
6. **Strict mode in development**:
   - `Model::preventLazyLoading()` - Catch N+1 queries
   - `Model::preventAccessingMissingAttributes()` - Catch missing attributes
   - `Model::preventSilentlyDiscardingAttributes()` - Catch discarded attributes
7. **Use strict types**: `declare(strict_types=1);` in all PHP files
8. **Type hints everywhere** - Use proper parameter and return type declarations
9. **Imports**: Always use "use" imports in PHP files instead of inline imports
10. **Policies**: Use the `#[UsePolicy]` attribute on models instead of manual policy registration in service providers
11. **Job Chaining**: When appropriate, use Laravel job chaining as described in the Laravel documentation

### Routes & Controllers
1. **Route organization**:
   - Web routes for Inertia pages
   - API routes only for external API access
   - Group routes by middleware
2. **Authorization**:
   - Handle authorization in routes or middleware, NOT in controllers
   - Use route middleware like `can:update,post` or custom middleware
   - Controllers should assume authorization has already been checked
3. **Controller methods**:
   - Return Inertia responses for web routes
   - Use proper HTTP status codes
   - Handle exceptions gracefully
4. **Route model binding** - Use implicit binding where possible
5. **Controller Constraints**:
   - Controllers should only use RESTful public methods or `__invoke`/`__construct`
   - If a controller needs a different method name, it likely needs to be another controller with RESTful patterns or should be invokable

[Rest of the document remains unchanged]