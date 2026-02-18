<?php

use App\Enums\PaymentStatus;
use App\Enums\WasteStatus;
use App\Models\Household;
use App\Models\Payment;
use App\Models\Waste;
use App\Models\WasteElectronic;
use App\Models\WasteOrganic;
use App\Models\WastePlastic;

beforeEach(function () {
    Household::query()->delete();
    Waste::query()->delete();
    Payment::query()->delete();
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

describe('paymentSummary', function () {
    it('returns 200 with correct structure', function () {
        $response = $this->getJson('/api/reports/payment-summary');

        $response->assertOk()
            ->assertJsonPath('message', 'Payment summary retrieved successfully.')
            ->assertJsonStructure([
                'message',
                'data' => [
                    'total_payments',
                    'by_status',
                    'confirmed_revenue',
                    'projected_revenue',
                ],
            ]);
    });

    it('returns correct counts by status', function () {
        Payment::factory()->count(2)->create(['status' => PaymentStatus::Pending->value]);
        Payment::factory()->count(3)->paid()->create();
        Payment::factory()->count(1)->failed()->create();

        $response = $this->getJson('/api/reports/payment-summary');

        $response->assertOk();

        $byStatus = collect($response->json('data.by_status'));

        expect($byStatus->firstWhere('status', 'pending')['count'])->toBe(2)
            ->and($byStatus->firstWhere('status', 'paid')['count'])->toBe(3)
            ->and($byStatus->firstWhere('status', 'failed')['count'])->toBe(1);
    });

    it('calculates confirmed_revenue from paid only', function () {
        Payment::factory()->count(2)->paid()->create(['amount' => '100.00']);
        Payment::factory()->count(1)->create(['status' => PaymentStatus::Pending->value, 'amount' => '200.00']);

        $response = $this->getJson('/api/reports/payment-summary');

        $response->assertOk()
            ->assertJsonPath('data.confirmed_revenue', '200.00');
    });

    it('calculates projected_revenue from pending and paid', function () {
        Payment::factory()->count(2)->paid()->create(['amount' => '100.00']);
        Payment::factory()->count(3)->create(['status' => PaymentStatus::Pending->value, 'amount' => '50.00']);
        Payment::factory()->count(1)->failed()->create(['amount' => '999.00']);

        $response = $this->getJson('/api/reports/payment-summary');

        $response->assertOk()
            ->assertJsonPath('data.projected_revenue', '350.00');
    });

    it('returns zeros when no payments exist', function () {
        $response = $this->getJson('/api/reports/payment-summary');

        $response->assertOk()
            ->assertJsonPath('data.total_payments', 0)
            ->assertJsonPath('data.confirmed_revenue', '0.00')
            ->assertJsonPath('data.projected_revenue', '0.00');

        $byStatus = collect($response->json('data.by_status'));

        expect($byStatus)->toHaveCount(3)
            ->and($byStatus->every(fn ($item) => $item['count'] === 0))->toBeTrue();
    });
});
