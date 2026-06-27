<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListPaymentsRequest;
use App\Http\Requests\Api\V1\ProcessPaymentRequest;
use App\Http\Resources\ApiResourceCollection;
use App\Http\Resources\PaymentResource;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    public function index(ListPaymentsRequest $request): ApiResourceCollection
    {
        $perPage = (int) ($request->validated('per_page') ?? 15);

        $payments = $this->paymentService->listAll($request->user(), $perPage);

        return PaymentResource::collection($payments);
    }

    public function forOrder(Request $request, int $order): ApiResourceCollection
    {
        $payments = $this->paymentService->listForOrder($order, $request->user());

        return PaymentResource::collection($payments);
    }

    public function store(ProcessPaymentRequest $request, int $order): JsonResponse
    {
        $payment = $this->paymentService->process($order, $request->user(), $request->validated());

        return (new PaymentResource($payment))
            ->response()
            ->setStatusCode(201);
    }
}
