<?php

use App\Models\User;

const VALID_PASSWORD = 'Password1';

it('registers a user and returns token', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => VALID_PASSWORD,
        'password_confirmation' => VALID_PASSWORD,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.user.email', 'john@example.com')
        ->assertJsonStructure(['data' => ['token', 'token_type', 'user']]);
});

it('rejects duplicate email on registration', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'taken@example.com',
        'password' => VALID_PASSWORD,
        'password_confirmation' => VALID_PASSWORD,
    ])->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_FAILED')
        ->assertJsonPath('error.message', 'This email address is already registered.');
});

it('rejects weak password on registration', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_FAILED')
        ->assertJsonPath('error.details.0.field', 'password');
});

it('rejects password mismatch on registration', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => VALID_PASSWORD,
        'password_confirmation' => 'Password2',
    ])->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_FAILED')
        ->assertJsonPath('error.message', 'The password confirmation does not match.');
});

it('returns validation errors for invalid registration', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'short',
    ]);

    $response->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_FAILED');
});

it('logs in with valid credentials', function () {
    User::factory()->create([
        'email' => 'login@example.com',
        'password' => VALID_PASSWORD,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'login@example.com',
        'password' => VALID_PASSWORD,
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['token', 'token_type']]);
});

it('rejects login with wrong email', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'missing@example.com',
        'password' => VALID_PASSWORD,
    ])->assertUnauthorized()
        ->assertJsonPath('error.code', 'INVALID_CREDENTIALS')
        ->assertJsonPath('error.message', 'The email or password you entered is incorrect.');
});

it('rejects login with wrong password', function () {
    User::factory()->create([
        'email' => 'login@example.com',
        'password' => VALID_PASSWORD,
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'login@example.com',
        'password' => 'WrongPass1',
    ])->assertUnauthorized()
        ->assertJsonPath('error.code', 'INVALID_CREDENTIALS')
        ->assertJsonPath('error.message', 'The email or password you entered is incorrect.');
});

it('returns validation errors for invalid login', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'not-an-email',
        'password' => VALID_PASSWORD,
    ])->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_FAILED')
        ->assertJsonPath('error.message', 'Please enter a valid email address.');
});

it('requires authentication for protected routes', function () {
    $this->getJson('/api/v1/orders')
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'UNAUTHENTICATED')
        ->assertJsonPath('error.message', 'You are not signed in. Please log in or create an account to access this resource.')
        ->assertJsonCount(3, 'error.details');
});

it('returns json unauthenticated when accept header is not set', function () {
    $this->get('/api/v1/orders?status=pending&per_page=15', [
        'Accept' => 'application/json',
    ])
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'UNAUTHENTICATED')
        ->assertJsonPath('error.message', 'You are not signed in. Please log in or create an account to access this resource.');
});
