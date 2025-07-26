<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('requires authentication for checkout subscription', function () {
    $response = $this->postJson('/api/checkout/subscription', [
        'price_id' => 'price_1234567890',
    ]);

    $response->assertStatus(401);
});

it('validates price_id for checkout subscription', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/checkout/subscription', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('price_id');
});

it('requires authentication for checkout lifetime', function () {
    $response = $this->postJson('/api/checkout/lifetime', [
        'price_id' => 'price_1234567890',
    ]);

    $response->assertStatus(401);
});

it('validates price_id for checkout lifetime', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/checkout/lifetime', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('price_id');
});

it('requires authentication for billing portal', function () {
    $response = $this->getJson('/api/billing/portal');

    $response->assertStatus(401);
});

it('returns error when user has no stripe id for billing portal', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/billing/portal');

    $response->assertStatus(404);
});

it('requires authentication for billing info', function () {
    $response = $this->getJson('/api/billing/info');

    $response->assertStatus(401);
});

it('returns user billing information', function () {
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

it('requires authentication for cancel subscription', function () {
    $response = $this->postJson('/api/billing/subscription/cancel', [
        'subscription_id' => 'sub_1234567890',
    ]);

    $response->assertStatus(401);
});

it('validates subscription_id for cancel subscription', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/billing/subscription/cancel', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('subscription_id');
});

it('requires authentication for resume subscription', function () {
    $response = $this->postJson('/api/billing/subscription/resume', [
        'subscription_id' => 'sub_1234567890',
    ]);

    $response->assertStatus(401);
});

it('validates subscription_id for resume subscription', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/billing/subscription/resume', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('subscription_id');
});
