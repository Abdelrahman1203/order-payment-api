<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\OrderHasPaymentsException;
use App\Exceptions\OrderLockedException;
use App\Exceptions\OrderNotFoundException;
use App\Models\Order;
use App\Models\User;
use App\Repositories\OrderRepository;
use App\Repositories\PaymentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly PaymentRepository $paymentRepository,
    ) {}

    public function list(User $user, ?OrderStatus $status, int $perPage): LengthAwarePaginator
    {
        return $this->orderRepository->paginateForUser($user, $status, $perPage);
    }

    public function get(int $orderId, User $user): Order
    {
        return $this->findOwnedOrder($orderId, $user);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Order
    {
        return $this->orderRepository->create($user, $data, $data['items']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $orderId, User $user, array $data): Order
    {
        $order = $this->findOwnedOrder($orderId, $user);

        if ($this->paymentRepository->hasSuccessfulPayment($order)) {
            throw new OrderLockedException;
        }

        $items = array_key_exists('items', $data) ? $data['items'] : null;

        return $this->orderRepository->update($order, $data, $items);
    }

    public function delete(int $orderId, User $user): void
    {
        $order = $this->findOwnedOrder($orderId, $user);

        if ($order->payments()->exists()) {
            throw new OrderHasPaymentsException;
        }

        $this->orderRepository->delete($order);
    }

    private function findOwnedOrder(int $orderId, User $user): Order
    {
        $order = $this->orderRepository->findForUser($orderId, $user);

        if ($order === null) {
            throw new OrderNotFoundException;
        }

        return $order;
    }
}
