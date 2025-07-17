<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('checkout subscription requires authentication', function () {
    $response = $this->postJson('/api/checkout/subscription', [
        'price_id' => 'price_1234567890',
    ]);

    $response->assertStatus(401);
});

test('checkout subscription validates price_id', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/checkout/subscription', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('price_id');
});

test('billing portal requires authentication', function () {
    $response = $this->getJson('/api/billing/portal');

    $response->assertStatus(401);
});

test('billing portal returns error when user has no stripe id', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/billing/portal');

    $response->assertStatus(404)
        ->assertJson([
            'error' => 'No billing information found',
        ]);
});

test('billing info requires authentication', function () {
    $response = $this->getJson('/api/billing/info');

    $response->assertStatus(401);
});

test('billing info returns user billing information', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/billing/info');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'has_stripe_id',
            'subscriptions',
            'licenses',
            'payment_methods',
        ]);
});
