<?php

namespace App\Repositories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderRepository
{
    public function paginateForUser(User $user, ?OrderStatus $status, int $perPage): LengthAwarePaginator
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->withCount('payments')
            ->with('items')
            ->latest()
            ->paginate($perPage);
    }

    public function findForUser(int $orderId, User $user): ?Order
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->with(['items', 'payments'])
            ->find($orderId);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $items
     */
    public function create(User $user, array $attributes, array $items): Order
    {
        return DB::transaction(function () use ($user, $attributes, $items): Order {
            $order = new Order([
                'customer_name' => $attributes['customer_name'],
                'customer_email' => $attributes['customer_email'],
                'status' => $attributes['status'] ?? OrderStatus::Pending,
                'total' => 0,
            ]);
            $order->user()->associate($user);
            $order->save();

            $this->syncItems($order, $items);

            return $order->fresh(['items', 'payments']);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>|null  $items
     */
    public function update(Order $order, array $attributes, ?array $items = null): Order
    {
        return DB::transaction(function () use ($order, $attributes, $items): Order {
            $order->fill([
                'customer_name' => $attributes['customer_name'] ?? $order->customer_name,
                'customer_email' => $attributes['customer_email'] ?? $order->customer_email,
                'status' => $attributes['status'] ?? $order->status,
            ]);

            $order->save();

            if ($items !== null) {
                $this->syncItems($order, $items);
            }

            return $order->fresh(['items', 'payments']);
        });
    }

    public function delete(Order $order): void
    {
        $order->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncItems(Order $order, array $items): void
    {
        $order->items()->delete();

        $total = '0.00';

        foreach ($items as $item) {
            $subtotal = bcmul((string) $item['price'], (string) $item['quantity'], 2);

            $order->items()->create([
                'product_name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'subtotal' => $subtotal,
            ]);

            $total = bcadd($total, $subtotal, 2);
        }

        $order->update(['total' => $total]);
    }
}
