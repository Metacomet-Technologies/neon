<?php

declare(strict_types=1);

use App\Events\LicenseAssigned;
use App\Events\LicenseTransferred;
use App\Exceptions\License\GuildAlreadyHasLicenseException;
use App\Exceptions\License\LicenseNotAssignedException;
use App\Exceptions\License\LicenseOnCooldownException;
use App\Models\Guild;
use App\Models\License;
use App\Models\User;
use Illuminate\Support\Facades\Event;

it('can create a license', function () {
    $user = User::factory()->create();
    $license = License::factory()->forUser($user)->create();

    expect($license)->toBeInstanceOf(License::class);
    expect($license->user_id)->toBe($user->id);
});

it('license belongs to user', function () {
    $user = User::factory()->create();
    $license = License::factory()->forUser($user)->create();

    expect($license->user)->toBeInstanceOf(User::class);
    expect($license->user->id)->toBe($user->id);
});

it('user has many licenses', function () {
    $user = User::factory()->create();
    License::factory()->forUser($user)->count(3)->create();

    expect($user->licenses->count())->toBe(3);
});

it('license status methods work correctly', function () {
    $activeLicense = License::factory()->active()->create();
    $parkedLicense = License::factory()->parked()->create();

    expect($activeLicense->isActive())->toBeTrue();
    expect($activeLicense->isParked())->toBeFalse();

    expect($parkedLicense->isParked())->toBeTrue();
    expect($parkedLicense->isActive())->toBeFalse();
});

it('license type methods work correctly', function () {
    $subscriptionLicense = License::factory()->subscription()->create();
    $lifetimeLicense = License::factory()->lifetime()->create();

    expect($subscriptionLicense->isSubscription())->toBeTrue();
    expect($subscriptionLicense->isLifetime())->toBeFalse();

    expect($lifetimeLicense->isLifetime())->toBeTrue();
    expect($lifetimeLicense->isSubscription())->toBeFalse();
});

it('license can be assigned to guild', function () {
    $license = License::factory()->parked()->create();
    $guild = Guild::factory()->create();

    // Ensure license is not on cooldown
    $license->update(['last_assigned_at' => now()->subDays(31)]);

    expect($license->isAssigned())->toBeFalse();

    $license->assignToGuild($guild);
    $license->refresh();

    expect($license->isAssigned())->toBeTrue();
    expect($license->isActive())->toBeTrue();
    expect($license->assigned_guild_id)->toBe($guild->id);
    expect($license->last_assigned_at)->not()->toBeNull();
});

it('license can be unassigned from guild', function () {
    $license = License::factory()->parked()->create();
    $guild = Guild::factory()->create();

    // First assign without cooldown
    $license->update(['last_assigned_at' => now()->subDays(31)]);
    $license->assignToGuild($guild);
    $license->refresh();

    expect($license->isAssigned())->toBeTrue();

    $license->unassign();
    $license->refresh();

    expect($license->isAssigned())->toBeFalse();
    expect($license->isParked())->toBeTrue();
    expect($license->assigned_guild_id)->toBeNull();
});

it('license scopes work correctly', function () {
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

it('license guild assignment scope works correctly', function () {
    $guildId = '987654321098765432';
    License::factory()->assignedToGuild($guildId)->count(2)->create();
    License::factory()->unassigned()->count(3)->create();

    expect(License::assignedToGuild($guildId)->count())->toBe(2);
    expect(License::unassigned()->count())->toBe(3);
});

it('license cooldown detection works correctly', function () {
    $license = License::factory()->onCooldown()->create();
    $freshLicense = License::factory()->create();

    expect($license->isOnCooldown())->toBeTrue();
    expect($license->getCooldownDaysRemaining())->toBeGreaterThan(0);
    expect($freshLicense->isOnCooldown())->toBeFalse();
    expect($freshLicense->getCooldownDaysRemaining())->toBe(0);
});

it('license cannot be assigned while on cooldown', function () {
    $license = License::factory()->onCooldown()->create();
    $guild = Guild::factory()->create();

    expect(fn () => $license->assignToGuild($guild))
        ->toThrow(LicenseOnCooldownException::class);
});

it('license cannot be assigned to guild that already has active license', function () {
    $guild = Guild::factory()->create();
    $existingLicense = License::factory()->parked()->create();

    // Assign first license without cooldown
    $existingLicense->update(['last_assigned_at' => now()->subDays(31)]);
    $existingLicense->assignToGuild($guild);

    $newLicense = License::factory()->parked()->create();
    $newLicense->update(['last_assigned_at' => now()->subDays(31)]);

    expect(fn () => $newLicense->assignToGuild($guild))
        ->toThrow(GuildAlreadyHasLicenseException::class);
});

it('license can be parked', function () {
    $guild = Guild::factory()->create();
    $license = License::factory()->parked()->create();

    // First assign without cooldown
    $license->update(['last_assigned_at' => now()->subDays(31)]);
    $license->assignToGuild($guild);
    $license->refresh();

    expect($license->isActive())->toBeTrue();
    expect($license->isAssigned())->toBeTrue();

    $license->park();
    $license->refresh();

    expect($license->isParked())->toBeTrue();
    expect($license->isAssigned())->toBeFalse();
    expect($license->assigned_guild_id)->toBeNull();
});

it('license transfer respects cooldown', function () {
    $license = License::factory()->onCooldown()->create();
    $guild = Guild::factory()->create();

    // Try to assign first (should fail due to cooldown)
    expect(fn () => $license->assignToGuild($guild))
        ->toThrow(LicenseOnCooldownException::class);
});

it('license transfer throws exception when not assigned', function () {
    $license = License::factory()->parked()->create();
    $guild = Guild::factory()->create();

    expect(fn () => $license->transferToGuild($guild))
        ->toThrow(LicenseNotAssignedException::class);
});

it('license transfer works correctly when not on cooldown', function () {
    $originalGuild = Guild::factory()->create();
    $newGuild = Guild::factory()->create();

    $license = License::factory()->parked()->create();
    // Set last_assigned_at to more than 30 days ago to avoid cooldown
    $license->update(['last_assigned_at' => now()->subDays(31)]);
    $license->assignToGuild($originalGuild);

    // Update last_assigned_at again to simulate a license that was assigned long ago
    $license->update(['last_assigned_at' => now()->subDays(31)]);
    $license->refresh();

    expect($license->assigned_guild_id)->toBe($originalGuild->id);
    expect($license->isOnCooldown())->toBeFalse();

    $license->transferToGuild($newGuild);
    $license->refresh();

    expect($license->assigned_guild_id)->toBe($newGuild->id);
    expect($license->isActive())->toBeTrue();
});

it('license assignment dispatches LicenseAssigned event', function () {
    Event::fake();

    $license = License::factory()->parked()->create();
    $guild = Guild::factory()->create();

    // Set last_assigned_at to more than 30 days ago to avoid cooldown
    $license->update(['last_assigned_at' => now()->subDays(31)]);

    $license->assignToGuild($guild);

    Event::assertDispatched(LicenseAssigned::class, function ($event) use ($license, $guild) {
        return $event->license->id === $license->id && $event->guild->id === $guild->id;
    });
});

it('license transfer dispatches LicenseTransferred event', function () {
    Event::fake();

    $originalGuild = Guild::factory()->create();
    $newGuild = Guild::factory()->create();

    $license = License::factory()->parked()->create();
    // Set last_assigned_at to more than 30 days ago to avoid cooldown
    $license->update(['last_assigned_at' => now()->subDays(31)]);
    $license->assignToGuild($originalGuild);

    // Update last_assigned_at again to simulate a license that was assigned long ago
    $license->update(['last_assigned_at' => now()->subDays(31)]);
    $license->refresh();

    $license->transferToGuild($newGuild);

    Event::assertDispatched(LicenseTransferred::class, function ($event) use ($license, $originalGuild, $newGuild) {
        return $event->license->id === $license->id
            && $event->fromGuild->id === $originalGuild->id
            && $event->toGuild->id === $newGuild->id;
    });
});

it('license assignment and transfer events contain correct data', function () {
    Event::fake();

    $license = License::factory()->parked()->create();
    $guild1 = Guild::factory()->create();
    $guild2 = Guild::factory()->create();

    // Set last_assigned_at to more than 30 days ago to avoid cooldown
    $license->update(['last_assigned_at' => now()->subDays(31)]);

    // Test assignment event
    $license->assignToGuild($guild1);

    Event::assertDispatched(LicenseAssigned::class, function ($event) use ($license, $guild1) {
        expect($event->license)->toBeInstanceOf(License::class);
        expect($event->guild)->toBeInstanceOf(Guild::class);
        expect($event->license->id)->toBe($license->id);
        expect($event->guild->id)->toBe($guild1->id);

        return true;
    });

    // Update last_assigned_at again to simulate a license that was assigned long ago
    $license->update(['last_assigned_at' => now()->subDays(31)]);
    $license->refresh();

    // Test transfer event
    $license->transferToGuild($guild2);

    Event::assertDispatched(LicenseTransferred::class, function ($event) use ($license, $guild1, $guild2) {
        expect($event->license)->toBeInstanceOf(License::class);
        expect($event->fromGuild)->toBeInstanceOf(Guild::class);
        expect($event->toGuild)->toBeInstanceOf(Guild::class);
        expect($event->license->id)->toBe($license->id);
        expect($event->fromGuild->id)->toBe($guild1->id);
        expect($event->toGuild->id)->toBe($guild2->id);

        return true;
    });
});
