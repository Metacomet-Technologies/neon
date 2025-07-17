<?php

// Example tests for the new invokable controllers
// These would be added to tests/Feature/LicenseControllerTest.php

use App\Models\Guild;
use App\Models\License;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;

uses(RefreshDatabase::class);

test('assign license controller requires authentication', function () {
    $license = License::factory()->create();
    $guild = Guild::factory()->create();

    $response = $this->postJson("/api/licenses/{$license->id}/assign", [
        'guild_id' => $guild->id,
    ]);

    $response->assertStatus(Response::HTTP_UNAUTHORIZED);
});

test('assign license controller works for license owner', function () {
    $user = User::factory()->create();
    $license = License::factory()->parked()->forUser($user)->create();
    $guild = Guild::factory()->create();

    // Set last_assigned_at to more than 30 days ago to avoid cooldown
    $license->update(['last_assigned_at' => now()->subDays(31)]);

    $response = $this->actingAs($user)->postJson("/api/licenses/{$license->id}/assign", [
        'guild_id' => $guild->id,
    ]);

    $response->assertStatus(Response::HTTP_OK)
        ->assertJson([
            'message' => 'License assigned successfully',
        ]);

    $license->refresh();
    expect($license->assigned_guild_id)->toBe($guild->id);
    expect($license->isActive())->toBeTrue();
});

test('park license controller works for license owner', function () {
    $user = User::factory()->create();
    $license = License::factory()->parked()->forUser($user)->create();
    $guild = Guild::factory()->create();

    // First assign the license
    $license->update(['last_assigned_at' => now()->subDays(31)]);
    $license->assignToGuild($guild);

    $response = $this->actingAs($user)->postJson("/api/licenses/{$license->id}/park");

    $response->assertStatus(Response::HTTP_OK)
        ->assertJson([
            'message' => 'License parked successfully',
        ]);

    $license->refresh();
    expect($license->assigned_guild_id)->toBeNull();
    expect($license->isParked())->toBeTrue();
});

test('transfer license controller works for license owner', function () {
    $user = User::factory()->create();
    $license = License::factory()->parked()->forUser($user)->create();
    $originalGuild = Guild::factory()->create();
    $newGuild = Guild::factory()->create();

    // First assign the license
    $license->update(['last_assigned_at' => now()->subDays(31)]);
    $license->assignToGuild($originalGuild);

    // Update again to avoid cooldown for transfer
    $license->update(['last_assigned_at' => now()->subDays(31)]);

    $response = $this->actingAs($user)->postJson("/api/licenses/{$license->id}/transfer", [
        'guild_id' => $newGuild->id,
    ]);

    $response->assertStatus(Response::HTTP_OK)
        ->assertJson([
            'message' => 'License transferred successfully',
        ]);

    $license->refresh();
    expect($license->assigned_guild_id)->toBe($newGuild->id);
    expect($license->isActive())->toBeTrue();
});

test('license controllers deny access to non-owners', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $license = License::factory()->parked()->forUser($owner)->create();
    $guild = Guild::factory()->create();

    // Test assign controller
    $response = $this->actingAs($otherUser)->postJson("/api/licenses/{$license->id}/assign", [
        'guild_id' => $guild->id,
    ]);
    $response->assertStatus(Response::HTTP_FORBIDDEN);

    // Test park controller
    $response = $this->actingAs($otherUser)->postJson("/api/licenses/{$license->id}/park");
    $response->assertStatus(Response::HTTP_FORBIDDEN);

    // Test transfer controller
    $response = $this->actingAs($otherUser)->postJson("/api/licenses/{$license->id}/transfer", [
        'guild_id' => $guild->id,
    ]);
    $response->assertStatus(Response::HTTP_FORBIDDEN);
});
