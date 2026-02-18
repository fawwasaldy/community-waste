<?php

use App\Enums\WasteStatus;
use App\Models\Household;
use App\Models\User;
use App\Models\Waste;
use App\Models\WasteOrganic;
use App\Models\WastePlastic;
use App\Services\ReportService;

beforeEach(function () {
    User::query()->delete();
    Household::query()->delete();
    Waste::query()->delete();
});

it('structures summary correctly with total, by_type, by_status, by_type_and_status', function () {
    WasteOrganic::factory()->count(2)->create(['status' => WasteStatus::Pending->value]);
    WastePlastic::factory()->count(3)->create(['status' => WasteStatus::Completed->value]);

    $service = app(ReportService::class);
    $summary = $service->getWasteSummary();

    expect($summary)->toHaveKeys(['total', 'by_type', 'by_status', 'by_type_and_status'])
        ->and($summary['total'])->toBe(5)
        ->and($summary['by_type'])->toHaveCount(4)
        ->and($summary['by_status'])->toHaveCount(4);

    $byType = collect($summary['by_type']);

    expect($byType->firstWhere('type', 'organic')['count'])->toBe(2)
        ->and($byType->firstWhere('type', 'plastic')['count'])->toBe(3)
        ->and($byType->firstWhere('type', 'paper')['count'])->toBe(0)
        ->and($byType->firstWhere('type', 'electronic')['count'])->toBe(0);

    $byStatus = collect($summary['by_status']);

    expect($byStatus->firstWhere('status', 'pending')['count'])->toBe(2)
        ->and($byStatus->firstWhere('status', 'completed')['count'])->toBe(3)
        ->and($byStatus->firstWhere('status', 'scheduled')['count'])->toBe(0)
        ->and($byStatus->firstWhere('status', 'canceled')['count'])->toBe(0);

    expect($summary['by_type_and_status'])->toHaveCount(16);

    $byTypeAndStatus = collect($summary['by_type_and_status']);

    expect($byTypeAndStatus->where('type', 'organic')->where('status', 'pending')->first()['count'])->toBe(2)
        ->and($byTypeAndStatus->where('type', 'plastic')->where('status', 'completed')->first()['count'])->toBe(3)
        ->and($byTypeAndStatus->where('type', 'paper')->where('status', 'pending')->first()['count'])->toBe(0);
});

it('returns zero total and empty arrays when no data', function () {
    $service = app(ReportService::class);
    $summary = $service->getWasteSummary();

    expect($summary['total'])->toBe(0)
        ->and($summary['by_type_and_status'])->toHaveCount(16)
        ->and($summary['by_type'])->toHaveCount(4)
        ->and($summary['by_status'])->toHaveCount(4);

    $byType = collect($summary['by_type']);
    $byStatus = collect($summary['by_status']);

    expect($byType->every(fn ($item) => $item['count'] === 0))->toBeTrue()
        ->and($byStatus->every(fn ($item) => $item['count'] === 0))->toBeTrue();
});
