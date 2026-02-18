<?php

use App\Enums\WasteStatus;
use App\Models\Household;
use App\Models\Waste;
use App\Models\WasteElectronic;
use App\Models\WasteOrganic;
use App\Models\WastePlastic;

beforeEach(function () {
    Household::query()->delete();
    Waste::query()->delete();
});

it('returns 200 with correct structure', function () {
    $response = $this->getJson('/api/reports/waste-summary');

    $response->assertOk()
        ->assertJsonPath('message', 'Waste summary retrieved successfully.')
        ->assertJsonStructure([
            'message',
            'data' => [
                'total',
                'by_type',
                'by_status',
                'by_type_and_status',
            ],
        ]);
});

it('returns correct counts grouped by type', function () {
    WasteOrganic::factory()->count(3)->create();
    WastePlastic::factory()->count(2)->create();
    WasteElectronic::factory()->count(1)->create();

    $response = $this->getJson('/api/reports/waste-summary');

    $response->assertOk();

    $byType = collect($response->json('data.by_type'));

    expect($byType->firstWhere('type', 'organic')['count'])->toBe(3)
        ->and($byType->firstWhere('type', 'plastic')['count'])->toBe(2)
        ->and($byType->firstWhere('type', 'electronic')['count'])->toBe(1)
        ->and($byType->firstWhere('type', 'paper')['count'])->toBe(0);
});

it('returns correct counts grouped by status', function () {
    WasteOrganic::factory()->count(2)->create(['status' => WasteStatus::Pending->value]);
    WasteOrganic::factory()->count(3)->create(['status' => WasteStatus::Scheduled->value]);
    WastePlastic::factory()->count(1)->create(['status' => WasteStatus::Completed->value]);

    $response = $this->getJson('/api/reports/waste-summary');

    $response->assertOk();

    $byStatus = collect($response->json('data.by_status'));

    expect($byStatus->firstWhere('status', 'pending')['count'])->toBe(2)
        ->and($byStatus->firstWhere('status', 'scheduled')['count'])->toBe(3)
        ->and($byStatus->firstWhere('status', 'completed')['count'])->toBe(1)
        ->and($byStatus->firstWhere('status', 'canceled')['count'])->toBe(0);
});

it('returns correct counts for type and status combinations', function () {
    WasteOrganic::factory()->count(2)->create(['status' => WasteStatus::Pending->value]);
    WasteOrganic::factory()->count(1)->create(['status' => WasteStatus::Scheduled->value]);
    WastePlastic::factory()->count(3)->create(['status' => WasteStatus::Completed->value]);

    $response = $this->getJson('/api/reports/waste-summary');

    $response->assertOk()
        ->assertJsonPath('data.total', 6);

    $byTypeAndStatus = collect($response->json('data.by_type_and_status'));

    expect($byTypeAndStatus)->toHaveCount(16)
        ->and($byTypeAndStatus->where('type', 'organic')->where('status', 'pending')->first()['count'])->toBe(2)
        ->and($byTypeAndStatus->where('type', 'organic')->where('status', 'scheduled')->first()['count'])->toBe(1)
        ->and($byTypeAndStatus->where('type', 'plastic')->where('status', 'completed')->first()['count'])->toBe(3)
        ->and($byTypeAndStatus->where('type', 'paper')->where('status', 'pending')->first()['count'])->toBe(0);
});

it('returns zeros and empty when no waste data exists', function () {
    $response = $this->getJson('/api/reports/waste-summary');

    $response->assertOk()
        ->assertJsonPath('data.total', 0);

    $byTypeAndStatus = collect($response->json('data.by_type_and_status'));

    expect($byTypeAndStatus)->toHaveCount(16)
        ->and($byTypeAndStatus->every(fn ($item) => $item['count'] === 0))->toBeTrue();

    $byType = collect($response->json('data.by_type'));
    $byStatus = collect($response->json('data.by_status'));

    expect($byType->every(fn ($item) => $item['count'] === 0))->toBeTrue()
        ->and($byStatus->every(fn ($item) => $item['count'] === 0))->toBeTrue()
        ->and($byType)->toHaveCount(4)
        ->and($byStatus)->toHaveCount(4);
});
