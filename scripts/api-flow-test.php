<?php

$base = 'http://127.0.0.1:8000/api/v1';
$token = null;
$orderId = null;
$passed = 0;
$failed = 0;
$results = [];

function request(string $method, string $url, ?array $body = null, ?string $token = null): array
{
    $ch = curl_init($url);
    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer {$token}";
    }

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
    ]);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $responseBody = substr($response, $headerSize);

    return [
        'status' => $status,
        'body' => json_decode($responseBody, true) ?? $responseBody,
        'raw' => $responseBody,
    ];
}

function test(string $name, int $expected, callable $fn): mixed
{
    global $passed, $failed, $results;
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "TEST: {$name}\n";
    echo str_repeat('=', 60) . "\n";

    $result = $fn();
    $ok = $result['status'] === $expected;

    echo "Expected: {$expected} | Got: {$result['status']} | " . ($ok ? 'PASS' : 'FAIL') . "\n";
    echo json_encode($result['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

    $results[] = ['name' => $name, 'expected' => $expected, 'actual' => $result['status'], 'ok' => $ok];
    $ok ? $passed++ : $failed++;

    return $result;
}

// 1. Register validation error
test('Register - validation error', 422, fn () => request('POST', "$base/auth/register", [
    'name' => '',
    'email' => 'not-email',
    'password' => 'short',
]));

// 2. Register success
$register = test('Register - success', 201, fn () => request('POST', "$base/auth/register", [
    'name' => 'Demo User',
    'email' => 'demo@example.com',
    'password' => 'Password1',
    'password_confirmation' => 'Password1',
]));
$token = $register['body']['data']['token'] ?? null;

// 3. Login invalid
test('Login - invalid credentials', 401, fn () => request('POST', "$base/auth/login", [
    'email' => 'demo@example.com',
    'password' => 'wrong-password',
]));

// 4. Login success
$login = test('Login - success', 200, fn () => request('POST', "$base/auth/login", [
    'email' => 'demo@example.com',
    'password' => 'Password1',
]));
$token = $login['body']['data']['token'] ?? $token;

// 5. Unauthorized
test('List orders - no auth', 401, fn () => request('GET', "$base/orders"));

// 6. Create order
$createOrder = test('Create order', 201, fn () => request('POST', "$base/orders", [
    'customer_name' => 'Alice Smith',
    'customer_email' => 'alice@example.com',
    'items' => [
        ['product_name' => 'Laptop', 'quantity' => 1, 'price' => 999.99],
        ['product_name' => 'Mouse', 'quantity' => 2, 'price' => 25.50],
    ],
], $token));
$orderId = $createOrder['body']['data']['id'] ?? null;

// Seed extra orders for pagination
for ($i = 1; $i <= 4; $i++) {
    request('POST', "$base/orders", [
        'customer_name' => "Bulk User {$i}",
        'customer_email' => "bulk{$i}@example.com",
        'items' => [['product_name' => 'Widget', 'quantity' => 1, 'price' => 10.00]],
    ], $token);
}

// 7. Pagination
test('List orders - pagination per_page=2', 200, fn () => request('GET', "$base/orders?per_page=2&page=1", null, $token));

// 8. Status filter
test('List orders - status=confirmed', 200, fn () => request('GET', "$base/orders?status=confirmed", null, $token));

// 9. Get order
test('Get order by ID', 200, fn () => request('GET', "$base/orders/{$orderId}", null, $token));

// 10. Payment on pending order
test('Payment on pending order', 422, fn () => request('POST', "$base/orders/{$orderId}/payments", [
    'payment_method' => 'credit_card',
], $token));

// 11. Confirm order
test('Confirm order', 200, fn () => request('PUT', "$base/orders/{$orderId}", [
    'status' => 'confirmed',
], $token));

// 12. Credit card payment
test('Payment - credit_card success', 201, fn () => request('POST', "$base/orders/{$orderId}/payments", [
    'payment_method' => 'credit_card',
    'metadata' => ['card_number' => '4242424242424242'],
], $token));

// 13. PayPal payment
test('Payment - paypal success', 201, fn () => request('POST', "$base/orders/{$orderId}/payments", [
    'payment_method' => 'paypal',
], $token));

// 14. Stripe fail (.99 total)
$stripeOrder = test('Create order for stripe fail', 201, fn () => request('POST', "$base/orders", [
    'customer_name' => 'Stripe Test',
    'customer_email' => 'stripe@example.com',
    'items' => [['product_name' => 'Test Item', 'quantity' => 1, 'price' => 19.99]],
], $token));
$stripeOrderId = $stripeOrder['body']['data']['id'];
request('PUT', "$base/orders/{$stripeOrderId}", ['status' => 'confirmed'], $token);
test('Payment - stripe fail (.99)', 201, fn () => request('POST', "$base/orders/{$stripeOrderId}/payments", [
    'payment_method' => 'stripe',
], $token));

// 15. PayPal fail (fail@ email)
$failOrder = test('Create order for paypal fail', 201, fn () => request('POST', "$base/orders", [
    'customer_name' => 'Fail User',
    'customer_email' => 'fail@example.com',
    'status' => 'confirmed',
    'items' => [['product_name' => 'Item', 'quantity' => 1, 'price' => 50.00]],
], $token));
$failOrderId = $failOrder['body']['data']['id'];
request('PUT', "$base/orders/{$failOrderId}", ['status' => 'confirmed'], $token);
test('Payment - paypal fail (fail@)', 201, fn () => request('POST', "$base/orders/{$failOrderId}/payments", [
    'payment_method' => 'paypal',
], $token));

// 16. List payments for order
test('List payments for order', 200, fn () => request('GET', "$base/orders/{$orderId}/payments", null, $token));

// 17. List all payments
test('List all payments - paginated', 200, fn () => request('GET', "$base/payments?per_page=2", null, $token));

// 18. Delete with payments
test('Delete order with payments', 409, fn () => request('DELETE', "$base/orders/{$orderId}", null, $token));

// 19. Delete without payments
$list = request('GET', "$base/orders?per_page=20", null, $token);
$deletable = null;
foreach ($list['body']['data'] ?? [] as $o) {
    if (($o['payments_count'] ?? 0) === 0) {
        $deletable = $o['id'];
        break;
    }
}
if ($deletable) {
    test('Delete order without payments', 204, fn () => request('DELETE', "$base/orders/{$deletable}", null, $token));
}

// 20. Pagination cap
$cap = test('Pagination cap per_page=500', 200, fn () => request('GET', "$base/orders?per_page=500", null, $token));
$perPage = $cap['body']['meta']['per_page'] ?? null;
echo "\n>>> per_page capped at: {$perPage} (expected max 100)\n";

// 21. Update order items recalculates total
test('Update order items - recalc total', 200, fn () => request('PUT', "$base/orders/{$orderId}", [
    'items' => [
        ['product_name' => 'Keyboard', 'quantity' => 1, 'price' => 75.00],
    ],
], $token));

echo "\n" . str_repeat('=', 60) . "\n";
echo "SUMMARY: {$passed} passed, {$failed} failed out of " . count($results) . " tests\n";
echo str_repeat('=', 60) . "\n";

if ($failed > 0) {
    foreach ($results as $r) {
        if (! $r['ok']) {
            echo "FAIL: {$r['name']} (expected {$r['expected']}, got {$r['actual']})\n";
        }
    }
    exit(1);
}

exit(0);
