<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateOrderRequest;
use App\Http\Requests\Api\V1\ListOrdersRequest;
use App\Http\Requests\Api\V1\UpdateOrderRequest;
use App\Http\Resources\ApiResourceCollection;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    public function index(ListOrdersRequest $request): ApiResourceCollection
    {
        $validated = $request->validated();

        $status = isset($validated['status'])
            ? OrderStatus::from($validated['status'])
            : null;

        $perPage = (int) ($validated['per_page'] ?? 15);

        $orders = $this->orderService->list($request->user(), $status, $perPage);

        return OrderResource::collection($orders);
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->create($request->user(), $request->validated());

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, int $order): OrderResource
    {
        return new OrderResource($this->orderService->get($order, $request->user()));
    }

    public function update(UpdateOrderRequest $request, int $order): OrderResource
    {
        return new OrderResource(
            $this->orderService->update($order, $request->user(), $request->validated())
        );
    }

    public function destroy(Request $request, int $order): JsonResponse
    {
        $this->orderService->delete($order, $request->user());

        return response()->json(null, 204);
    }
}
