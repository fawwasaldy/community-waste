<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function wasteSummary(ReportService $service): JsonResponse
    {
        $summary = $service->getWasteSummary();

        return response()->json([
            'message' => 'Waste summary retrieved successfully.',
            'data' => $summary,
        ]);
    }

    public function paymentSummary(ReportService $service): JsonResponse
    {
        $summary = $service->getPaymentSummary();

        return response()->json([
            'message' => 'Payment summary retrieved successfully.',
            'data' => $summary,
        ]);
    }

    public function householdHistory(string $id, ReportService $service): JsonResponse
    {
        $history = $service->getHouseholdHistory($id);

        return response()->json([
            'message' => 'Household history retrieved successfully.',
            'data' => $history,
        ]);
    }
}
