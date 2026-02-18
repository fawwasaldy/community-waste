<?php

use App\Enums\WasteStatus;
use App\Models\Household;
use App\Models\User;
use App\Models\Waste;
use App\Models\WasteOrganic;
use App\Models\WastePlastic;
use App\Repositories\ReportRepository;

beforeEach(function () {
    User::query()->delete();
    Household::query()->delete();
    Waste::query()->delete();
});

it('returns grouped counts by type and status', function () {
    WasteOrganic::factory()->count(2)->create(['status' => WasteStatus::Pending->value]);
    WasteOrganic::factory()->count(1)->create(['status' => WasteStatus::Scheduled->value]);
    WastePlastic::factory()->count(3)->create(['status' => WasteStatus::Completed->value]);

    $repository = new ReportRepository;
    $results = $repository->getWasteSummary();

    $collection = collect($results);

    expect($collection)->toHaveCount(3)
        ->and($collection->where('type', 'organic')->where('status', 'pending')->first()['count'])->toBe(2)
        ->and($collection->where('type', 'organic')->where('status', 'scheduled')->first()['count'])->toBe(1)
        ->and($collection->where('type', 'plastic')->where('status', 'completed')->first()['count'])->toBe(3);
});

it('returns empty array when no waste data', function () {
    $repository = new ReportRepository;
    $results = $repository->getWasteSummary();

    expect($results)->toBeArray()->toBeEmpty();
});
