# License Controller Refactoring

## Overview

The `LicenseController` has been refactored to follow Laravel conventions by moving non-standard methods to invokable controllers. This improves code organization and follows the single responsibility principle.

## Changes Made

### 1. Moved Non-Standard Methods to Invokable Controllers

The following methods were moved from `LicenseController` to separate invokable controllers:

- `assign()` → `App\Http\Controllers\Api\License\AssignLicenseController`
- `park()` → `App\Http\Controllers\Api\License\ParkLicenseController`
- `transfer()` → `App\Http\Controllers\Api\License\TransferLicenseController`

### 2. Updated LicenseController

The main `LicenseController` now only contains standard resource controller methods:
- `index()` - Get user's licenses
- `show()` - Get a specific license

### 3. File Structure

```
app/Http/Controllers/Api/
├── LicenseController.php (standard resource methods only)
└── License/
    ├── AssignLicenseController.php
    ├── ParkLicenseController.php
    └── TransferLicenseController.php
```

## Route Examples

```php
// Standard resource routes
Route::apiResource('licenses', LicenseController::class)->only(['index', 'show']);

// Invokable controllers for license operations
Route::post('licenses/{license}/assign', AssignLicenseController::class)->name('licenses.assign');
Route::post('licenses/{license}/park', ParkLicenseController::class)->name('licenses.park');
Route::post('licenses/{license}/transfer', TransferLicenseController::class)->name('licenses.transfer');
```

## Benefits

1. **Standards Compliance**: The main controller now only contains standard resource methods
2. **Single Responsibility**: Each controller has a single, focused responsibility
3. **Improved Testing**: Each operation can be tested independently
4. **Better Organization**: Related functionality is grouped logically
5. **Maintainability**: Easier to modify individual operations without affecting others

## Authorization

All controllers maintain the same authorization logic using the `LicensePolicy`:
- Only license owners can perform operations on their licenses
- Each controller uses the appropriate policy method (`assign`, `park`, `transfer`)

## Error Handling

The invokable controllers maintain the same error handling as the original methods:
- Validation errors for required fields
- Domain-specific exceptions (cooldown, guild conflicts, etc.)
- Consistent JSON responses
