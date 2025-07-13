<?php

declare(strict_types=1);

use App\Models\License;
use App\Models\User;

test('license can be created', function () {
    $user = User::factory()->create();
    $license = License::factory()->forUser($user)->create();

    expect($license)->toBeInstanceOf(License::class);
    expect($license->user_id)->toBe($user->id);
});

test('license belongs to user', function () {
    $user = User::factory()->create();
    $license = License::factory()->forUser($user)->create();

    expect($license->user)->toBeInstanceOf(User::class);
    expect($license->user->id)->toBe($user->id);
});

test('user has many licenses', function () {
    $user = User::factory()->create();
    License::factory()->forUser($user)->count(3)->create();

    expect($user->licenses->count())->toBe(3);
});

test('license status methods work correctly', function () {
    $activeLicense = License::factory()->active()->create();
    $parkedLicense = License::factory()->parked()->create();

    expect($activeLicense->isActive())->toBeTrue();
    expect($activeLicense->isParked())->toBeFalse();

    expect($parkedLicense->isParked())->toBeTrue();
    expect($parkedLicense->isActive())->toBeFalse();
});

test('license type methods work correctly', function () {
    $subscriptionLicense = License::factory()->subscription()->create();
    $lifetimeLicense = License::factory()->lifetime()->create();

    expect($subscriptionLicense->isSubscription())->toBeTrue();
    expect($subscriptionLicense->isLifetime())->toBeFalse();

    expect($lifetimeLicense->isLifetime())->toBeTrue();
    expect($lifetimeLicense->isSubscription())->toBeFalse();
});

test('license can be assigned to guild', function () {
    $license = License::factory()->parked()->create();
    $guildId = '123456789012345678';

    expect($license->isAssigned())->toBeFalse();

    $license->assignToGuild($guildId);
    $license->refresh();

    expect($license->isAssigned())->toBeTrue();
    expect($license->isActive())->toBeTrue();
    expect($license->assigned_guild_id)->toBe($guildId);
    expect($license->last_assigned_at)->not()->toBeNull();
});

test('license can be unassigned from guild', function () {
    $license = License::factory()->active()->create();
    $license->assignToGuild('123456789012345678');
    $license->refresh();

    expect($license->isAssigned())->toBeTrue();

    $license->unassign();
    $license->refresh();

    expect($license->isAssigned())->toBeFalse();
    expect($license->isParked())->toBeTrue();
    expect($license->assigned_guild_id)->toBeNull();
});

test('license scopes work correctly', function () {
    $user = User::factory()->create();

    // Create licenses with specific states
    License::factory()->active()->subscription()->forUser($user)->count(1)->create();
    License::factory()->active()->lifetime()->forUser($user)->count(1)->create();
    License::factory()->parked()->subscription()->forUser($user)->count(2)->create();
    License::factory()->parked()->lifetime()->forUser($user)->count(1)->create();

    expect($user->activeLicenses()->count())->toBe(2);
    expect($user->parkedLicenses()->count())->toBe(3);
    expect(License::subscription()->count())->toBe(3);
    expect(License::lifetime()->count())->toBe(2);
});

test('license guild assignment scope works correctly', function () {
    $guildId = '987654321098765432';
    License::factory()->assignedToGuild($guildId)->count(2)->create();
    License::factory()->unassigned()->count(3)->create();

    expect(License::assignedToGuild($guildId)->count())->toBe(2);
    expect(License::unassigned()->count())->toBe(3);
});
