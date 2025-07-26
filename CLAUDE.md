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

### Frontend (React/TypeScript)
1. **Component libraries**: Always use Catalyst components first, then UI components, before creating custom components
2. **Prefer Inertia.js over API calls** - Use `router.post()` instead of `axios` for form submissions
3. **TypeScript interfaces** - Define proper interfaces for all props and data structures
4. **No inline styles** - Use Tailwind CSS classes
5. **Component structure**: Functional components with hooks
6. **Flash messages**: Handle through Inertia's page props, not API responses
7. **Naming conventions**:
   - Variables and functions: `camelCase`
   - Components: `PascalCase`
   - CSS classes: `kebab-case`
8. **Always use absolute imports in TypeScript files**

### Database & Models
1. **Explicit relationships** - Always define both sides of relationships
2. **Use scopes** - Create query scopes for common queries
3. **Timestamps** - All tables should have timestamps
4. **Soft deletes** - Consider using soft deletes for important data
5. **Migrations** - Always include down() methods

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

### Testing
1. **Use Pest PHP** for testing
2. **Test structure**: Feature tests for user flows, unit tests for business logic
3. **Database**: Use RefreshDatabase trait for tests
4. **Factories**: Create comprehensive factories for all models

### Git & Version Control
1. **Commit messages**: Clear, concise descriptions of changes
2. **Branch naming**: feature/*, bugfix/*, hotfix/*
3. **PR descriptions**: Include summary and test plan

## Project-Specific Context

### Key Models
- **User**: Discord authenticated users with Stripe customer info
- **License**: Software licenses (subscription or lifetime)
- **Guild**: Discord servers
- **NeonCommand**: Custom bot commands
- **WelcomeSetting**: Per-guild welcome message settings

### Business Rules
1. **Licenses**:
   - Can be assigned to one guild at a time
   - Have a 30-day cooldown period between transfers
   - Two types: subscription (recurring) and lifetime (one-time)
   - Status: active (assigned) or parked (unassigned)

2. **Permissions**:
   - Admins bypass all authorization checks via Gate::before
   - Users can only manage their own licenses
   - Guild management requires Discord ADMINISTRATOR permission

3. **Billing**:
   - Stripe webhooks create licenses automatically
   - Subscriptions create licenses on successful payment
   - Lifetime licenses created on checkout completion

### Common Tasks & Patterns

#### Adding a new feature
1. Create migration if needed
2. Update model with relationships and scopes
3. Create/update Nova resource
4. Add routes (web for UI, API if needed)
5. Create controller with Inertia responses
6. Build React component with TypeScript
7. Add tests

#### Working with licenses
```php
// Assign license
$license->assignToGuild($guild);

// Park license
$license->park();

// Transfer license
$license->transferToGuild($newGuild);
```

#### Flash messages
```php
// Backend
return redirect()->back()->with('success', 'Action completed!');

// Frontend
{props.flash?.success && (
  <div className="alert-success">{props.flash.success}</div>
)}
```

## Development Environment
- Local environment uses `APP_ENV=local`
- Model unguarding and strict checks enabled in local only
- Use Laravel Herd for local development
- Node.js managed via nvm

## Important Files
- `/app/Providers/AppServiceProvider.php` - Core app configuration
- `/resources/js/Layout/Layout.tsx` - Main layout component
- `/routes/web.php` - Inertia routes
- `/app/Http/Controllers/BillingController.php` - Main billing logic

## Commands to Run
- `composer install` - Install PHP dependencies
- `npm install` - Install Node dependencies
- `npm run dev` - Start Vite dev server
- `php artisan migrate` - Run migrations
- `php artisan db:seed` - Seed database
- `npm run build` - Build for production

## Notes for AI Assistants
1. Always check existing patterns before implementing new features
2. Prefer modifying existing code over creating new files
3. Use Inertia.js patterns, not separate API/frontend
4. Follow the established naming conventions
5. Keep responses concise and to the point
6. Run linting/type checking after changes
