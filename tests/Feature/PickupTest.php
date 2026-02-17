<?php

use App\Enums\PaymentStatus;
use App\Enums\WasteStatus;
use App\Models\Household;
use App\Models\Payment;
use App\Models\User;
use App\Models\Waste;
use App\Models\WasteElectronic;
use App\Models\WasteOrganic;
use App\Models\WastePaper;
use App\Models\WastePlastic;

beforeEach(function () {
    User::query()->delete();
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

describe('schedule', function () {
    it('returns 401 when unauthenticated', function () {
        $waste = WasteOrganic::factory()->create();

        $response = $this->putJson("/api/pickups/{$waste->_id}/schedule", [
            'pickup_date' => now()->format('Y-m-d'),
        ]);

        $response->assertUnauthorized();
    });

    it('schedules a pending pickup', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $waste = WasteOrganic::factory()->create();
        $pickupDate = now()->format('Y-m-d');

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/pickups/{$waste->_id}/schedule", [
                'pickup_date' => $pickupDate,
            ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.status', 'scheduled');

        expect($waste->fresh()->pickup_date->format('Y-m-d'))->toBe($pickupDate);
    });

    it('rejects scheduling a non-pending pickup', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $waste = WasteOrganic::factory()->create([
            'status' => WasteStatus::Completed->value,
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/pickups/{$waste->_id}/schedule", [
                'pickup_date' => now()->format('Y-m-d'),
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    });

    it('rejects pickup_date before waste created_at', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $waste = WasteOrganic::factory()->create([
            'created_at' => now(),
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/pickups/{$waste->_id}/schedule", [
                'pickup_date' => now()->subDay()->format('Y-m-d'),
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['pickup_date']);
    });

    it('rejects organic pickup_date more than 3 days after created_at', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $waste = WasteOrganic::factory()->create([
            'created_at' => now(),
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/pickups/{$waste->_id}/schedule", [
                'pickup_date' => now()->addDays(4)->format('Y-m-d'),
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['pickup_date']);
    });

    it('allows organic pickup_date exactly 3 days after created_at', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $waste = WasteOrganic::factory()->create([
            'created_at' => now(),
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/pickups/{$waste->_id}/schedule", [
                'pickup_date' => now()->addDays(3)->format('Y-m-d'),
            ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.status', 'scheduled');
    });

    it('allows non-organic pickup_date more than 3 days after created_at', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $waste = WastePlastic::factory()->create([
            'created_at' => now(),
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/pickups/{$waste->_id}/schedule", [
                'pickup_date' => now()->addDays(10)->format('Y-m-d'),
            ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.status', 'scheduled');
    });

    it('returns 404 for non-existent pickup id', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson('/api/pickups/nonexistent-id/schedule', [
                'pickup_date' => now()->format('Y-m-d'),
            ]);

        $response->assertNotFound();
    });
});

describe('complete', function () {
    it('returns 401 when unauthenticated', function () {
        $waste = WasteOrganic::factory()->create([
            'status' => WasteStatus::Scheduled->value,
        ]);

        $response = $this->putJson("/api/pickups/{$waste->_id}/complete");

        $response->assertUnauthorized();
    });

    it('completes a scheduled pickup', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $waste = WasteOrganic::factory()->create([
            'status' => WasteStatus::Scheduled->value,
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/pickups/{$waste->_id}/complete");

        $response->assertSuccessful()
            ->assertJsonPath('data.status', 'completed');

        expect($waste->fresh()->status)->toBe(WasteStatus::Completed);
    });

    it('creates a payment with correct amount for organic waste', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $waste = WasteOrganic::factory()->create([
            'status' => WasteStatus::Scheduled->value,
        ]);

        $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/pickups/{$waste->_id}/complete");

        $payment = Payment::where('household_id', $waste->household_id)->first();

        expect($payment)->not->toBeNull()
            ->and((int) $payment->amount)->toBe(50000)
            ->and($payment->status)->toBe(PaymentStatus::Pending)
            ->and($payment->payment_date)->toBeNull()
            ->and($payment->household_id)->toBe($waste->household_id);
    });

    it('creates a payment with correct amount for electronic waste', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $waste = WasteElectronic::factory()->create([
            'status' => WasteStatus::Scheduled->value,
        ]);

        $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/pickups/{$waste->_id}/complete");

        $payment = Payment::where('household_id', $waste->household_id)->first();

        expect($payment)->not->toBeNull()
            ->and((int) $payment->amount)->toBe(100000);
    });

    it('rejects completing a non-scheduled pickup', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $waste = WasteOrganic::factory()->create([
            'status' => WasteStatus::Pending->value,
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/pickups/{$waste->_id}/complete");

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    });

    it('returns 404 for non-existent pickup id', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson('/api/pickups/nonexistent-id/complete');

        $response->assertNotFound();
    });
});

describe('cancel', function () {
    it('returns 401 when unauthenticated', function () {
        $waste = WasteOrganic::factory()->create([
            'status' => WasteStatus::Scheduled->value,
        ]);

        $response = $this->putJson("/api/pickups/{$waste->_id}/cancel");

        $response->assertUnauthorized();
    });

    it('cancels a scheduled pickup', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $waste = WasteOrganic::factory()->create([
            'status' => WasteStatus::Scheduled->value,
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/pickups/{$waste->_id}/cancel");

        $response->assertSuccessful()
            ->assertJsonPath('data.status', 'canceled');

        expect($waste->fresh()->status)->toBe(WasteStatus::Canceled);
    });

    it('rejects canceling a non-scheduled pickup', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $waste = WasteOrganic::factory()->create([
            'status' => WasteStatus::Pending->value,
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/pickups/{$waste->_id}/cancel");

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    });

    it('returns 404 for non-existent pickup id', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson('/api/pickups/nonexistent-id/cancel');

        $response->assertNotFound();
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
