# Neon Project Context & Best Practices

Laravel application for managing Discord servers with billing/licensing features.

## Stack
- Laravel 12, Inertia.js, React, TypeScript, Tailwind CSS
- Discord OAuth, Stripe (Cashier), Laravel Nova, Reverb, Pulse
- MySQL/PostgreSQL with Eloquent ORM

## Critical Coding Standards

### PHP/Laravel
1. **Use `final` classes** for services, DTOs, controllers
2. **No extending base Controller** - standalone classes only
3. **Authentication**: ALWAYS use `$request->user()`, NEVER `auth()` or `Auth::`
5. **Strict types**: `declare(strict_types=1);` in all files
6. **Type hints**: Always use parameter/return types
7. **Model::unguard()** enabled globally
8. **Development strict mode**:
   - `Model::preventLazyLoading()`
   - `Model::preventAccessingMissingAttributes()`
   - `Model::preventSilentlyDiscardingAttributes()`

### Frontend (React/TypeScript)
1. **Component priority**: Catalyst > UI components > custom
2. **Use Inertia**: `router.post()`, not `axios`
3. **TypeScript interfaces** for all props
4. **Tailwind only** - no inline styles
5. **Functional components** with hooks
6. **Absolute imports** in TypeScript

### Models & Database
1. **Define both sides** of relationships
2. **Use query scopes** for common queries
3. **Always include** timestamps and down() in migrations

### Routes & Controllers
1. **Authorization in routes/middleware**, NOT controllers
2. **Controllers assume** authorization already checked
3. **Web routes** return Inertia responses
4. **Use implicit** route model binding

### Naming Conventions
- Variables: `camelCase`
- Routes: `kebab-case`
- Database: `snake_case`
- Components: `PascalCase`

## Key Business Rules

### Licenses
- One guild at a time, 30-day transfer cooldown
- Types: subscription or lifetime
- Status: active (assigned) or parked

### Permissions
- Admins bypass all checks via Gate::before
- Users manage only their own licenses
- Guild management needs Discord ADMINISTRATOR

## Common Patterns

```php
// Authentication (ALWAYS use this)
$user = $request->user();

// License operations
$license->assignToGuild($guild);
$license->park();
$license->transferToGuild($newGuild);

// Flash messages
return redirect()->back()->with('success', 'Done!');
```

## Quick Reference

### Commands
- `composer run dev` - Start all services with hot reload
- `php artisan neon:start --watch` - Run bot with file watcher
- `php artisan queue:listen` - Auto-reloading queue worker

### Key Files
- AppServiceProvider.php - Core config
- Layout.tsx - Main layout
- web.php - Inertia routes

## IMPORTANT AI INSTRUCTIONS
1. **ALWAYS use `$request->user()`** for auth
2. **NEVER create new files** unless essential
3. **Follow existing patterns** exactly
4. **Keep responses SHORT**
5. **Run linting after changes**

## Development Tools

- **Use duster not pint**
