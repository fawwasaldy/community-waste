<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\WasteStatus;
use App\Enums\WasteType;
use App\Models\Household;
use App\Repositories\HouseholdRepository;
use App\Repositories\ReportRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ReportService
{
    public function __construct(
        private ReportRepository $reportRepository,
        private HouseholdRepository $householdRepository,
    ) {}

    /**
     * @return array{total: int, by_type: array<int, array{type: string, count: int}>, by_status: array<int, array{status: string, count: int}>, by_type_and_status: array<int, array{type: string, status: string, count: int}>}
     */
    public function getWasteSummary(): array
    {
        $rawData = $this->reportRepository->getWasteSummary();

        $byTypeMap = [];
        $byStatusMap = [];
        $total = 0;

        foreach ($rawData as $item) {
            $type = $item['type'];
            $status = $item['status'];
            $count = $item['count'];

            $total += $count;

            $byTypeMap[$type] = ($byTypeMap[$type] ?? 0) + $count;
            $byStatusMap[$status] = ($byStatusMap[$status] ?? 0) + $count;
        }

        $byType = [];
        foreach (WasteType::cases() as $type) {
            $byType[] = [
                'type' => $type->value,
                'count' => $byTypeMap[$type->value] ?? 0,
            ];
        }

        $byStatus = [];
        foreach (WasteStatus::cases() as $status) {
            $byStatus[] = [
                'status' => $status->value,
                'count' => $byStatusMap[$status->value] ?? 0,
            ];
        }

        $byTypeAndStatusMap = [];
        foreach ($rawData as $item) {
            $byTypeAndStatusMap[$item['type']][$item['status']] = $item['count'];
        }

        $byTypeAndStatus = [];
        foreach (WasteType::cases() as $type) {
            foreach (WasteStatus::cases() as $status) {
                $byTypeAndStatus[] = [
                    'type' => $type->value,
                    'status' => $status->value,
                    'count' => $byTypeAndStatusMap[$type->value][$status->value] ?? 0,
                ];
            }
        }

        return [
            'total' => $total,
            'by_type' => $byType,
            'by_status' => $byStatus,
            'by_type_and_status' => $byTypeAndStatus,
        ];
    }

    /**
     * @return array{total_payments: int, by_status: array<int, array{status: string, count: int}>, confirmed_revenue: string, projected_revenue: string}
     */
    public function getPaymentSummary(): array
    {
        $rawData = $this->reportRepository->getPaymentSummary();

        $totalPayments = 0;
        $byStatus = [];

        foreach (PaymentStatus::cases() as $status) {
            $entry = $rawData[$status->value] ?? ['count' => 0, 'total_amount' => '0.00'];
            $totalPayments += $entry['count'];
            $byStatus[] = [
                'status' => $status->value,
                'count' => $entry['count'],
            ];
        }

        $paidAmount = (float) ($rawData[PaymentStatus::Paid->value]['total_amount'] ?? '0.00');
        $pendingAmount = (float) ($rawData[PaymentStatus::Pending->value]['total_amount'] ?? '0.00');

        return [
            'total_payments' => $totalPayments,
            'by_status' => $byStatus,
            'confirmed_revenue' => number_format($paidAmount, 2, '.', ''),
            'projected_revenue' => number_format($paidAmount + $pendingAmount, 2, '.', ''),
        ];
    }

    /**
     * @return array{household_id: string, pickups: array{total: int, by_status: array<int, array{status: string, count: int}>, by_type: array<int, array{type: string, count: int}>}, payments: array{total: int, by_status: array<int, array{status: string, count: int}>, total_revenue: string}}
     *
     * @throws ModelNotFoundException
     */
    public function getHouseholdHistory(string $householdId): array
    {
        $household = $this->householdRepository->find($householdId);
        if ($household === null) {
            throw (new ModelNotFoundException)->setModel(Household::class);
        }

        $rawPickups = $this->reportRepository->getHouseholdPickupHistory($householdId);

        $byTypeMap = [];
        $byStatusMap = [];
        $pickupTotal = 0;

        foreach ($rawPickups as $item) {
            $pickupTotal += $item['count'];
            $byTypeMap[$item['type']] = ($byTypeMap[$item['type']] ?? 0) + $item['count'];
            $byStatusMap[$item['status']] = ($byStatusMap[$item['status']] ?? 0) + $item['count'];
        }

        $byStatus = [];
        foreach (WasteStatus::cases() as $status) {
            $byStatus[] = ['status' => $status->value, 'count' => $byStatusMap[$status->value] ?? 0];
        }

        $byType = [];
        foreach (WasteType::cases() as $type) {
            $byType[] = ['type' => $type->value, 'count' => $byTypeMap[$type->value] ?? 0];
        }

        $rawPayments = $this->reportRepository->getHouseholdPaymentHistory($householdId);
        $paymentTotal = 0;
        $payByStatus = [];

        foreach (PaymentStatus::cases() as $status) {
            $entry = $rawPayments[$status->value] ?? ['count' => 0];
            $paymentTotal += $entry['count'];
            $payByStatus[] = ['status' => $status->value, 'count' => $entry['count']];
        }

        $paidAmount = (float) ($rawPayments[PaymentStatus::Paid->value]['total_amount'] ?? '0.00');
        $pendingAmount = (float) ($rawPayments[PaymentStatus::Pending->value]['total_amount'] ?? '0.00');

        return [
            'household_id' => (string) $household->_id,
            'pickups' => [
                'total' => $pickupTotal,
                'by_status' => $byStatus,
                'by_type' => $byType,
            ],
            'payments' => [
                'total' => $paymentTotal,
                'by_status' => $payByStatus,
                'confirmed_revenue' => number_format($paidAmount, 2, '.', ''),
                'projected_revenue' => number_format($paidAmount + $pendingAmount, 2, '.', ''),
            ],
        ];
    }
}
