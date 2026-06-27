<?php

namespace App\Repositories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PaymentRepository
{
    public function paginateForUser(User $user, int $perPage): LengthAwarePaginator
    {
        return Payment::query()
            ->whereHas('order', fn ($query) => $query->where('user_id', $user->id))
            ->with('order')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, Payment>
     */
    public function forOrder(Order $order): Collection
    {
        return $order->payments()->latest()->get();
    }

    public function hasSuccessfulPayment(Order $order): bool
    {
        return $order->payments()
            ->where('status', PaymentStatus::Successful)
            ->exists();
    }

    public function create(
        Order $order,
        string $paymentMethod,
        string $amount,
        PaymentStatus $status,
        ?string $gatewayReference = null,
        ?string $failureReason = null,
    ): Payment {
        return Payment::query()->create([
            'order_id' => $order->id,
            'payment_method' => $paymentMethod,
            'amount' => $amount,
            'status' => $status,
            'gateway_reference' => $gatewayReference,
            'failure_reason' => $failureReason,
        ]);
    }

    public function updateResult(
        Payment $payment,
        PaymentStatus $status,
        ?string $gatewayReference = null,
        ?string $failureReason = null,
    ): Payment {
        $payment->update([
            'status' => $status,
            'gateway_reference' => $gatewayReference,
            'failure_reason' => $failureReason,
        ]);

        return $payment->fresh();
    }
}
