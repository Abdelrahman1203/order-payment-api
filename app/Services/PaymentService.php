<?php

namespace App\Services;

use App\DTOs\PaymentContext;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\OrderAlreadyPaidException;
use App\Exceptions\OrderNotConfirmedException;
use App\Exceptions\OrderNotFoundException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Repositories\OrderRepository;
use App\Repositories\PaymentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly PaymentRepository $paymentRepository,
        private readonly PaymentGatewayManager $gatewayManager,
    ) {}

    public function listAll(User $user, int $perPage): LengthAwarePaginator
    {
        return $this->paymentRepository->paginateForUser($user, $perPage);
    }

    /**
     * @return Collection<int, Payment>
     */
    public function listForOrder(int $orderId, User $user): Collection
    {
        $order = $this->findOrder($orderId, $user);

        return $this->paymentRepository->forOrder($order);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function process(int $orderId, User $user, array $data): Payment
    {
        $order = $this->findOrder($orderId, $user);

        if ($order->status !== OrderStatus::Confirmed) {
            throw new OrderNotConfirmedException;
        }

        if ($this->paymentRepository->hasSuccessfulPayment($order)) {
            throw new OrderAlreadyPaidException;
        }

        $paymentMethod = $data['payment_method'];
        $gateway = $this->gatewayManager->resolve($paymentMethod);

        return DB::transaction(function () use ($order, $data, $paymentMethod, $gateway): Payment {
            $payment = $this->paymentRepository->create(
                order: $order,
                paymentMethod: $paymentMethod,
                amount: (string) $order->total,
                status: PaymentStatus::Pending,
            );

            $context = new PaymentContext(
                orderId: $order->id,
                amount: (string) $order->total,
                customerEmail: $order->customer_email,
                metadata: $data['metadata'] ?? [],
            );

            $result = $gateway->process($context);

            return $this->paymentRepository->updateResult(
                payment: $payment,
                status: $result->status,
                gatewayReference: $result->gatewayReference,
                failureReason: $result->failureReason,
            );
        });
    }

    private function findOrder(int $orderId, User $user): Order
    {
        $order = $this->orderRepository->findForUser($orderId, $user);

        if ($order === null) {
            throw new OrderNotFoundException;
        }

        return $order;
    }
}
