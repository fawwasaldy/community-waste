<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmPaymentRequest;
use App\Http\Requests\IndexPaymentRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function index(IndexPaymentRequest $request, PaymentService $service): JsonResponse
    {
        $filters = $request->only(['status', 'household_id', 'payment_date_from', 'payment_date_to']);
        $perPage = $request->query('per_page', 10);
        $disablePagination = filter_var($request->query('disable_pagination', false), FILTER_VALIDATE_BOOLEAN);

        $payments = $service->getPayments($filters, (int) $perPage, $disablePagination);

        $data = PaymentResource::collection($payments)->response()->getData(true);

        return response()->json(['message' => 'Payments retrieved successfully.'] + $data);
    }

    public function store(StorePaymentRequest $request, PaymentService $service): JsonResponse
    {
        $payment = $service->createPayment($request->validated());

        $data = new PaymentResource($payment)->response()->getData(true);

        return response()->json(['message' => 'Payment created successfully.'] + $data, 201);
    }

    public function confirm(ConfirmPaymentRequest $request, string $id, PaymentService $service): JsonResponse
    {
        $payment = $service->confirmPayment($id, $request->validated());

        $data = new PaymentResource($payment)->response()->getData(true);

        return response()->json(['message' => 'Payment confirmed successfully.'] + $data);
    }
}
