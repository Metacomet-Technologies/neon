<?php

it('requires authentication for checkout', function () {
    $response = $this->postJson('/api/checkout/subscription', [
        'price_id' => 'price_1234567890',
    ]);

    $response->assertStatus(401);
});
