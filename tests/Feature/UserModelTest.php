<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('has a name', function () {
    expect($this->user->name)->toBeString();
});

it('has an email', function () {
    expect($this->user->email)->toBeString();
});

it('can be created', function () {
    $user = User::factory()->make();
    expect($user)->toBeInstanceOf(User::class);
});

it('can be saved', function () {
    $user = User::factory()->make();
    $user->save();
    expect($user->exists)->toBeTrue();
});

it('can be deleted', function () {
    $user = User::factory()->create();
    $user->delete();
    expect($user->exists)->toBeFalse();
});

it('can be updated', function () {
    $user = User::factory()->create();
    $user->update(['name' => 'John Doe']);
    expect($user->name)->toBe('John Doe');
});

it('can be found by id', function () {
    $user = User::factory()->create();
    $foundUser = User::find($user->id);
    expect($foundUser->id)->toBe($user->id);
});

it('can be found by email', function () {
    $user = User::factory()->create();
    $foundUser = User::where('email', $user->email)->first();
    expect($foundUser->email)->toBe($user->email);
});

it('can be found by name', function () {
    $user = User::factory()->create();
    $foundUser = User::where('name', $user->name)->first();
    expect($foundUser->name)->toBe($user->name);
});

it('can return the email_verified_at attribute', function () {
    $user = User::factory()->create();
    expect($user->email_verified_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

it('can return the password attribute', function () {
    $user = User::factory()->create();
    expect($user->password)->toBeString();
});
