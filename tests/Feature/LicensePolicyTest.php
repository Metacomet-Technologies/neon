<?php

declare(strict_types=1);

use App\Models\License;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('license policy allows owner to view their license', function () {
    $user = User::factory()->create();
    $license = License::factory()->forUser($user)->create();

    expect($user->can('view', $license))->toBeTrue();
});

test('license policy denies non-owner from viewing license', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $license = License::factory()->forUser($owner)->create();

    expect($otherUser->can('view', $license))->toBeFalse();
});

test('license policy allows owner to update their license', function () {
    $user = User::factory()->create();
    $license = License::factory()->forUser($user)->create();

    expect($user->can('update', $license))->toBeTrue();
});

test('license policy denies non-owner from updating license', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $license = License::factory()->forUser($owner)->create();

    expect($otherUser->can('update', $license))->toBeFalse();
});

test('license policy allows owner to assign their license', function () {
    $user = User::factory()->create();
    $license = License::factory()->forUser($user)->create();

    expect($user->can('assign', $license))->toBeTrue();
});

test('license policy denies non-owner from assigning license', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $license = License::factory()->forUser($owner)->create();

    expect($otherUser->can('assign', $license))->toBeFalse();
});

test('license policy allows owner to park their license', function () {
    $user = User::factory()->create();
    $license = License::factory()->forUser($user)->create();

    expect($user->can('park', $license))->toBeTrue();
});

test('license policy denies non-owner from parking license', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $license = License::factory()->forUser($owner)->create();

    expect($otherUser->can('park', $license))->toBeFalse();
});

test('license policy allows owner to transfer their license', function () {
    $user = User::factory()->create();
    $license = License::factory()->forUser($user)->create();

    expect($user->can('transfer', $license))->toBeTrue();
});

test('license policy denies non-owner from transferring license', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $license = License::factory()->forUser($owner)->create();

    expect($otherUser->can('transfer', $license))->toBeFalse();
});

test('license policy denies all users from creating licenses', function () {
    $user = User::factory()->create();

    expect($user->can('create', License::class))->toBeFalse();
});

test('license policy denies all users from deleting licenses', function () {
    $user = User::factory()->create();
    $license = License::factory()->forUser($user)->create();

    expect($user->can('delete', $license))->toBeFalse();
});

test('admin can bypass license policy through Gate::before', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $otherUser = User::factory()->create();
    $license = License::factory()->forUser($otherUser)->create();

    // Admin should be able to perform any action due to Gate::before
    expect($admin->can('view', $license))->toBeTrue();
    expect($admin->can('update', $license))->toBeTrue();
    expect($admin->can('assign', $license))->toBeTrue();
    expect($admin->can('park', $license))->toBeTrue();
    expect($admin->can('transfer', $license))->toBeTrue();
});
