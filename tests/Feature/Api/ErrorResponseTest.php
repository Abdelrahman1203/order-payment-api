<?php

it('returns meaningful envelope for unknown route', function () {
    $this->getJson('/api/v1/does-not-exist')
        ->assertNotFound()
        ->assertJsonPath('error.code', 'ROUTE_NOT_FOUND')
        ->assertJsonStructure(['error' => ['code', 'message', 'details']]);
});

it('returns meaningful envelope for invalid token', function () {
    $this->withHeader('Authorization', 'Bearer invalid-token')
        ->getJson('/api/v1/orders')
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'UNAUTHENTICATED')
        ->assertJsonPath('error.message', 'You are not signed in. Please log in or create an account to access this resource.')
        ->assertJsonCount(3, 'error.details');
});

it('returns first validation error as message for single field failure', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'not-an-email',
        'password' => 'Password1',
        'password_confirmation' => 'Password1',
    ]);

    $response->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_FAILED')
        ->assertJsonPath('error.message', 'Please enter a valid email address.');
});

it('returns summary message for multiple validation errors', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => '',
        'email' => 'bad',
        'password' => 'short',
    ]);

    $response->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_FAILED')
        ->assertJsonPath('error.message', 'The given data was invalid. Please review the field errors below.')
        ->assertJsonStructure(['error' => ['details']]);
});

it('returns meaningful not found for missing order', function () {
    $user = \App\Models\User::factory()->create();

    $this->actingAsJwt($user)
        ->getJson('/api/v1/orders/99999')
        ->assertNotFound()
        ->assertJsonPath('error.code', 'ORDER_NOT_FOUND')
        ->assertJsonPath('error.message', 'Order not found.');
});
