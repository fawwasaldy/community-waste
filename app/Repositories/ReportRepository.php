<?php

namespace App\Repositories;

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
}
