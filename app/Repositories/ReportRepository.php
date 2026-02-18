<?php

namespace App\Repositories;

use App\Models\Payment;
use App\Models\Waste;

class ReportRepository
{
    /**
     * @return array<int, array{type: string, status: string, count: int}>
     */
    public function getWasteSummary(): array
    {
        $results = Waste::raw(fn ($collection) => $collection->aggregate([
            [
                '$group' => [
                    '_id' => [
                        'type' => '$type',
                        'status' => '$status',
                    ],
                    'count' => ['$sum' => 1],
                ],
            ],
        ]));

        $summary = [];

        foreach ($results as $result) {
            $id = (array) $result->_id;
            $summary[] = [
                'type' => $id['type'],
                'status' => $id['status'],
                'count' => $result->count,
            ];
        }

        return $summary;
    }

    /**
     * @return array<string, array{status: string, count: int, total_amount: string}>
     */
    public function getPaymentSummary(): array
    {
        $results = Payment::raw(fn ($collection) => $collection->aggregate([
            [
                '$group' => [
                    '_id' => '$status',
                    'count' => ['$sum' => 1],
                    'total_amount' => ['$sum' => '$amount'],
                ],
            ],
        ]));

        $summary = [];
        foreach ($results as $result) {
            $status = (string) $result->_id;
            $summary[$status] = [
                'status' => $status,
                'count' => $result->count,
                'total_amount' => sprintf('%.2f', (string) $result->total_amount),
            ];
        }

        return $summary;
    }

    /**
     * @return array<int, array{type: string, status: string, count: int}>
     */
    public function getHouseholdPickupHistory(string $householdId): array
    {
        $results = Waste::raw(fn ($collection) => $collection->aggregate([
            [
                '$match' => ['household_id' => $householdId],
            ],
            [
                '$group' => [
                    '_id' => [
                        'type' => '$type',
                        'status' => '$status',
                    ],
                    'count' => ['$sum' => 1],
                ],
            ],
        ]));

        $summary = [];
        foreach ($results as $result) {
            $id = (array) $result->_id;
            $summary[] = [
                'type' => $id['type'],
                'status' => $id['status'],
                'count' => $result->count,
            ];
        }

        return $summary;
    }

    /**
     * @return array<string, array{status: string, count: int, total_amount: string}>
     */
    public function getHouseholdPaymentHistory(string $householdId): array
    {
        $results = Payment::raw(fn ($collection) => $collection->aggregate([
            [
                '$match' => ['household_id' => $householdId],
            ],
            [
                '$group' => [
                    '_id' => '$status',
                    'count' => ['$sum' => 1],
                    'total_amount' => ['$sum' => '$amount'],
                ],
            ],
        ]));

        $summary = [];
        foreach ($results as $result) {
            $status = (string) $result->_id;
            $summary[$status] = [
                'status' => $status,
                'count' => $result->count,
                'total_amount' => sprintf('%.2f', (string) $result->total_amount),
            ];
        }

        return $summary;
    }
}
