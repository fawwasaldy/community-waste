<?php

use App\Enums\PaymentStatus;
use App\Models\Household;
use App\Models\Payment;
use App\Models\User;

beforeEach(function () {
    User::query()->delete();
    Household::query()->delete();
    Payment::query()->delete();
});

describe('store', function () {
    it('returns 401 when unauthenticated', function () {
        $household = Household::factory()->create();

        $response = $this->postJson('/api/payments', [
            'household_id' => $household->_id,
            'amount' => 50000,
        ]);

        $response->assertUnauthorized();
    });

    it('creates a payment with correct attributes', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $household = Household::factory()->create();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/payments', [
                'household_id' => $household->_id,
                'amount' => 50000,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.household_id', $household->_id)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.payment_date', null);

        $payment = Payment::where('household_id', $household->_id)->first();

        expect($payment)->not->toBeNull()
            ->and($payment->status)->toBe(PaymentStatus::Pending)
            ->and($payment->payment_date)->toBeNull();
    });

    it('returns 201 with correct JSON structure', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $household = Household::factory()->create();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/payments', [
                'household_id' => $household->_id,
                'amount' => 75000,
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'household_id', 'amount', 'payment_date', 'status', 'created_at', 'updated_at'],
            ]);
    });

    it('rejects missing household_id', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/payments', [
                'amount' => 50000,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['household_id']);
    });

    it('rejects missing amount', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $household = Household::factory()->create();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/payments', [
                'household_id' => $household->_id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    });

    it('rejects invalid household_id', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/payments', [
                'household_id' => 'nonexistent-id',
                'amount' => 50000,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['household_id']);
    });
});
