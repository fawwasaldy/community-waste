<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexPaymentRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    public function index(IndexPaymentRequest $request, PaymentService $service): AnonymousResourceCollection
    {
        $filters = $request->only(['status', 'household_id', 'payment_date_from', 'payment_date_to']);
        $perPage = $request->query('per_page', 10);

        $payments = $service->getPayments($filters, (int) $perPage);

        return PaymentResource::collection($payments);
    }

    public function store(StorePaymentRequest $request, PaymentService $service): JsonResponse
    {
        $payment = $service->createPayment($request->validated());

        return new PaymentResource($payment)
            ->response()
            ->setStatusCode(201);
    }
}
