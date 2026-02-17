<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function store(StorePaymentRequest $request, PaymentService $service): JsonResponse
    {
        $payment = $service->createPayment($request->validated());

        return new PaymentResource($payment)
            ->response()
            ->setStatusCode(201);
    }
}
