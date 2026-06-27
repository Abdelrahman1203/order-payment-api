<?php

use App\Models\User;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = User::factory()->create();
$repo = app(OrderRepository::class);

for ($i = 0; $i < 5; $i++) {
    $items = [['product_name' => 'Product', 'quantity' => 2, 'price' => 10.00]];
    $repo->create($user, [
        'customer_name' => "Customer {$i}",
        'customer_email' => "c{$i}@example.com",
    ], $items);
}

DB::enableQueryLog();
DB::flushQueryLog();
$repo->paginateForUser($user, null, 15);
$listQueries = count(DB::getQueryLog());

DB::flushQueryLog();
$repo->findForUser(1, $user);
$detailQueries = count(DB::getQueryLog());

echo "Order list (5 orders, eager items + payments_count): {$listQueries} queries\n";
echo "Order detail (items + payments): {$detailQueries} queries\n";
