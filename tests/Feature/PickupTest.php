<?php

use App\Enums\WasteStatus;
use App\Models\Household;
use App\Models\Payment;
use App\Models\Waste;
use App\Models\WasteOrganic;
use App\Models\WastePaper;
use App\Models\WastePlastic;

beforeEach(function () {
    Household::query()->delete();
    Waste::query()->delete();
});

describe('index', function () {
    it('returns paginated pickups', function () {
        WasteOrganic::factory()->count(3)->create();

        $response = $this->getJson('/api/pickups');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'household_id', 'type', 'status', 'created_at', 'updated_at']],
                'links',
                'meta',
            ]);
    });

    it('filters by status', function () {
        WasteOrganic::factory()->create(['status' => WasteStatus::Pending->value]);
        WasteOrganic::factory()->create(['status' => WasteStatus::Completed->value]);

        $response = $this->getJson('/api/pickups?status=pending');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'pending');
    });

    it('filters by type', function () {
        WasteOrganic::factory()->create();
        WastePlastic::factory()->create();
        WastePaper::factory()->create();

        $response = $this->getJson('/api/pickups?type=organic');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'organic');
    });

    it('filters by household_id', function () {
        $household = Household::factory()->create();
        WasteOrganic::factory()->create(['household_id' => $household->_id]);
        WasteOrganic::factory()->create();

        $response = $this->getJson('/api/pickups?household_id='.$household->_id);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.household_id', $household->_id);
    });

    it('combines multiple filters', function () {
        $household = Household::factory()->create();
        WasteOrganic::factory()->create(['household_id' => $household->_id, 'status' => WasteStatus::Pending->value]);
        WasteOrganic::factory()->create(['household_id' => $household->_id, 'status' => WasteStatus::Completed->value]);
        WastePlastic::factory()->create(['household_id' => $household->_id, 'status' => WasteStatus::Pending->value]);

        $response = $this->getJson('/api/pickups?household_id='.$household->_id.'&status=pending&type=organic');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('respects per_page parameter', function () {
        WasteOrganic::factory()->count(5)->create();

        $response = $this->getJson('/api/pickups?per_page=2');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2);
    });

    it('returns empty data when no matches', function () {
        WasteOrganic::factory()->create(['status' => WasteStatus::Completed->value]);

        $response = $this->getJson('/api/pickups?status=pending');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    });
});

describe('store', function () {
    it('creates an organic pickup', function () {
        $household = Household::factory()->create();

        $response = $this->postJson('/api/pickups', [
            'household_id' => $household->_id,
            'type' => 'organic',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'organic')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.household_id', $household->_id);
    });

    it('creates a plastic pickup', function () {
        $household = Household::factory()->create();

        $response = $this->postJson('/api/pickups', [
            'household_id' => $household->_id,
            'type' => 'plastic',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'plastic')
            ->assertJsonPath('data.status', 'pending');
    });

    it('creates a paper pickup', function () {
        $household = Household::factory()->create();

        $response = $this->postJson('/api/pickups', [
            'household_id' => $household->_id,
            'type' => 'paper',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'paper')
            ->assertJsonPath('data.status', 'pending');
    });

    it('creates an electronic pickup with safety_check', function () {
        $household = Household::factory()->create();

        $response = $this->postJson('/api/pickups', [
            'household_id' => $household->_id,
            'type' => 'electronic',
            'safety_check' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'electronic')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.safety_check', true);
    });

    it('rejects an invalid type', function () {
        $household = Household::factory()->create();

        $response = $this->postJson('/api/pickups', [
            'household_id' => $household->_id,
            'type' => 'invalid',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    });

    it('rejects missing household_id', function () {
        $response = $this->postJson('/api/pickups', [
            'type' => 'organic',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['household_id']);
    });

    it('rejects when household has unpaid payments', function () {
        $household = Household::factory()->create();
        Payment::factory()->for($household)->create();

        $response = $this->postJson('/api/pickups', [
            'household_id' => $household->_id,
            'type' => 'organic',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['household_id']);
    });
});

describe('cancel:expired-organic-waste', function () {
    it('cancels pending organic waste older than 3 days', function () {
        $waste = WasteOrganic::factory()->create([
            'status' => WasteStatus::Pending->value,
            'created_at' => now()->subDays(4),
        ]);

        $this->artisan('cancel:expired-organic-waste')
            ->assertSuccessful();

        expect($waste->fresh()->status)->toBe(WasteStatus::Canceled);
    });

    it('cancels scheduled organic waste older than 3 days', function () {
        $waste = WasteOrganic::factory()->create([
            'status' => WasteStatus::Scheduled->value,
            'created_at' => now()->subDays(4),
        ]);

        $this->artisan('cancel:expired-organic-waste')
            ->assertSuccessful();

        expect($waste->fresh()->status)->toBe(WasteStatus::Canceled);
    });

    it('does not cancel completed organic waste', function () {
        $waste = WasteOrganic::factory()->create([
            'status' => WasteStatus::Completed->value,
            'created_at' => now()->subDays(4),
        ]);

        $this->artisan('cancel:expired-organic-waste')
            ->assertSuccessful();

        expect($waste->fresh()->status)->toBe(WasteStatus::Completed);
    });

    it('does not cancel organic waste younger than 3 days', function () {
        $waste = WasteOrganic::factory()->create([
            'status' => WasteStatus::Pending->value,
            'created_at' => now()->subDay(),
        ]);

        $this->artisan('cancel:expired-organic-waste')
            ->assertSuccessful();

        expect($waste->fresh()->status)->toBe(WasteStatus::Pending);
    });

    it('does not cancel non-organic waste older than 3 days', function () {
        $waste = WastePlastic::factory()->create([
            'status' => WasteStatus::Pending->value,
            'created_at' => now()->subDays(4),
        ]);

        $this->artisan('cancel:expired-organic-waste')
            ->assertSuccessful();

        expect($waste->fresh()->status)->toBe(WasteStatus::Pending);
    });
});
