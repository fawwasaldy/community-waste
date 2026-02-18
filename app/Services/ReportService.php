<?php

namespace App\Services;

use App\Enums\WasteStatus;
use App\Enums\WasteType;
use App\Repositories\ReportRepository;

class ReportService
{
    public function __construct(private ReportRepository $reportRepository) {}

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
}
