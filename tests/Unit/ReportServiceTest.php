<?php

use App\Enums\PaymentStatus;
use App\Enums\WasteStatus;
use App\Models\Household;
use App\Models\Payment;
use App\Models\User;
use App\Models\Waste;
use App\Models\WasteOrganic;
use App\Models\WastePlastic;
use App\Services\ReportService;

beforeEach(function () {
    User::query()->delete();
    Household::query()->delete();
    Waste::query()->delete();
    Payment::query()->delete();
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

describe('getPaymentSummary', function () {
    it('structures payment summary correctly with total, by_status, and revenues', function () {
        Payment::factory()->count(2)->create(['status' => PaymentStatus::Pending->value, 'amount' => '100.00']);
        Payment::factory()->count(3)->paid()->create(['amount' => '50.00']);
        Payment::factory()->count(1)->failed()->create(['amount' => '25.00']);

        $service = app(ReportService::class);
        $summary = $service->getPaymentSummary();

        expect($summary)->toHaveKeys(['total_payments', 'by_status', 'confirmed_revenue', 'projected_revenue'])
            ->and($summary['total_payments'])->toBe(6)
            ->and($summary['by_status'])->toHaveCount(3)
            ->and($summary['confirmed_revenue'])->toBe('150.00')
            ->and($summary['projected_revenue'])->toBe('350.00');

        $byStatus = collect($summary['by_status']);

        expect($byStatus->firstWhere('status', 'pending')['count'])->toBe(2)
            ->and($byStatus->firstWhere('status', 'paid')['count'])->toBe(3)
            ->and($byStatus->firstWhere('status', 'failed')['count'])->toBe(1);
    });

    it('returns zeros when no payments exist', function () {
        $service = app(ReportService::class);
        $summary = $service->getPaymentSummary();

        expect($summary['total_payments'])->toBe(0)
            ->and($summary['by_status'])->toHaveCount(3)
            ->and($summary['confirmed_revenue'])->toBe('0.00')
            ->and($summary['projected_revenue'])->toBe('0.00');

        $byStatus = collect($summary['by_status']);

        expect($byStatus->every(fn ($item) => $item['count'] === 0))->toBeTrue();
    });
});

describe('getHouseholdHistory', function () {
    it('structures household history correctly', function () {
        $household = Household::factory()->create();

        WasteOrganic::factory()->count(2)->create([
            'household_id' => (string) $household->_id,
            'status' => WasteStatus::Pending->value,
        ]);
        WastePlastic::factory()->count(1)->create([
            'household_id' => (string) $household->_id,
            'status' => WasteStatus::Completed->value,
        ]);
        Payment::factory()->count(2)->paid()->create([
            'household_id' => (string) $household->_id,
            'amount' => '100.00',
        ]);

        $service = app(ReportService::class);
        $result = $service->getHouseholdHistory((string) $household->_id);

        expect($result)->toHaveKeys(['household_id', 'pickups', 'payments'])
            ->and($result['pickups'])->toHaveKeys(['total', 'by_status', 'by_type'])
            ->and($result['pickups']['by_status'])->toHaveCount(4)
            ->and($result['pickups']['by_type'])->toHaveCount(4)
            ->and($result['payments'])->toHaveKeys(['total', 'by_status', 'confirmed_revenue', 'projected_revenue'])
            ->and($result['payments']['by_status'])->toHaveCount(3);
    });

    it('fills zeros for missing statuses and types', function () {
        $household = Household::factory()->create();

        WasteOrganic::factory()->count(1)->create([
            'household_id' => (string) $household->_id,
            'status' => WasteStatus::Pending->value,
        ]);

        $service = app(ReportService::class);
        $result = $service->getHouseholdHistory((string) $household->_id);

        $byStatus = collect($result['pickups']['by_status']);
        $byType = collect($result['pickups']['by_type']);

        expect($byStatus->firstWhere('status', 'pending')['count'])->toBe(1)
            ->and($byStatus->firstWhere('status', 'scheduled')['count'])->toBe(0)
            ->and($byStatus->firstWhere('status', 'completed')['count'])->toBe(0)
            ->and($byStatus->firstWhere('status', 'canceled')['count'])->toBe(0)
            ->and($byType->firstWhere('type', 'organic')['count'])->toBe(1)
            ->and($byType->firstWhere('type', 'plastic')['count'])->toBe(0)
            ->and($byType->firstWhere('type', 'paper')['count'])->toBe(0)
            ->and($byType->firstWhere('type', 'electronic')['count'])->toBe(0);
    });

    it('calculates confirmed_revenue from paid and projected_revenue from paid and pending', function () {
        $household = Household::factory()->create();

        Payment::factory()->count(2)->paid()->create([
            'household_id' => (string) $household->_id,
            'amount' => '100.00',
        ]);
        Payment::factory()->count(1)->pending()->create([
            'household_id' => (string) $household->_id,
            'amount' => '50.00',
        ]);
        Payment::factory()->count(1)->failed()->create([
            'household_id' => (string) $household->_id,
            'amount' => '30.00',
        ]);

        $service = app(ReportService::class);
        $result = $service->getHouseholdHistory((string) $household->_id);

        expect($result['payments']['confirmed_revenue'])->toBe('200.00')
            ->and($result['payments']['projected_revenue'])->toBe('250.00');
    });

    it('returns zeros when household has no data', function () {
        $household = Household::factory()->create();

        $service = app(ReportService::class);
        $result = $service->getHouseholdHistory((string) $household->_id);

        expect($result['pickups']['total'])->toBe(0)
            ->and($result['payments']['total'])->toBe(0)
            ->and($result['payments']['confirmed_revenue'])->toBe('0.00')
            ->and($result['payments']['projected_revenue'])->toBe('0.00');

        $byStatus = collect($result['pickups']['by_status']);
        $byType = collect($result['pickups']['by_type']);
        $payByStatus = collect($result['payments']['by_status']);

        expect($byStatus->every(fn ($item) => $item['count'] === 0))->toBeTrue()
            ->and($byType->every(fn ($item) => $item['count'] === 0))->toBeTrue()
            ->and($payByStatus->every(fn ($item) => $item['count'] === 0))->toBeTrue();
    });

    it('throws ModelNotFoundException when household not found', function () {
        $service = app(ReportService::class);

        expect(fn () => $service->getHouseholdHistory('000000000000000000000000'))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });
});
