<?php

use App\Models\Household;

beforeEach(function () {
    Household::query()->delete();
});

describe('index', function () {
    it('returns paginated households', function () {
        Household::factory()->count(3)->create();

        $response = $this->getJson('/api/households');

        $response->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'owner_name', 'address', 'block', 'no', 'created_at', 'updated_at']]]);
    });

    it('searches households by keyword', function () {
        Household::factory()->create(['owner_name' => 'John Doe']);
        Household::factory()->create(['owner_name' => 'Jane Smith']);

        $response = $this->getJson('/api/households?search=John');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.owner_name', 'John Doe');
    });

    it('filters households by block', function () {
        Household::factory()->create(['block' => 'A']);
        Household::factory()->create(['block' => 'B']);

        $response = $this->getJson('/api/households?block=A');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.block', 'A');
    });

    it('filters households by block and no', function () {
        Household::factory()->create(['block' => 'A', 'no' => '1']);
        Household::factory()->create(['block' => 'A', 'no' => '2']);

        $response = $this->getJson('/api/households?block=A&no=1');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.no', '1');
    });

    it('returns all results without pagination when disable_pagination is true', function () {
        Household::factory()->count(15)->create();

        $response = $this->getJson('/api/households?disable_pagination=1');

        $response->assertSuccessful()
            ->assertJsonCount(15, 'data')
            ->assertJsonMissingPath('meta')
            ->assertJsonMissingPath('links');
    });

    it('returns empty results when no match', function () {
        Household::factory()->create(['owner_name' => 'John Doe']);

        $response = $this->getJson('/api/households?search=NonExistent');

        $response->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });
});

describe('store', function () {
    it('creates a new household', function () {
        $data = [
            'owner_name' => 'John Doe',
            'address' => '123 Main St',
            'block' => 'A',
            'no' => '1',
        ];

        $response = $this->postJson('/api/households', $data);

        $response->assertCreated()
            ->assertJsonPath('data.owner_name', 'John Doe')
            ->assertJsonPath('data.block', 'A');

        $this->assertDatabaseHas('households', ['owner_name' => 'John Doe']);
    });

    it('fails with validation errors', function () {
        $response = $this->postJson('/api/households', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['owner_name', 'address']);
    });
});

describe('show', function () {
    it('returns a household by id', function () {
        $household = Household::factory()->create();

        $response = $this->getJson("/api/households/{$household->_id}");

        $response->assertSuccessful()
            ->assertJsonPath('data.id', $household->_id);
    });

    it('returns 404 for invalid id', function () {
        $response = $this->getJson('/api/households/nonexistent-id');

        $response->assertNotFound();
    });
});

describe('update', function () {
    it('updates a household partially', function () {
        $household = Household::factory()->create(['owner_name' => 'Old Name']);

        $response = $this->putJson("/api/households/{$household->_id}", ['owner_name' => 'New Name']);

        $response->assertSuccessful()
            ->assertJsonPath('data.owner_name', 'New Name');
    });

    it('returns 404 for invalid id', function () {
        $response = $this->putJson('/api/households/nonexistent-id', ['owner_name' => 'Test']);

        $response->assertNotFound();
    });
});

describe('destroy', function () {
    it('soft deletes a household', function () {
        $household = Household::factory()->create();

        $response = $this->deleteJson("/api/households/{$household->_id}");

        $response->assertSuccessful()
            ->assertJson(['message' => 'Household deleted successfully.']);

        $this->assertSoftDeleted($household);
        expect(Household::withTrashed()->find($household->_id))->not->toBeNull();
    });

    it('excludes soft-deleted household from index', function () {
        $household = Household::factory()->create();
        $household->delete();

        $response = $this->getJson('/api/households');

        $response->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    it('returns 404 for soft-deleted household on show', function () {
        $household = Household::factory()->create();
        $household->delete();

        $response = $this->getJson("/api/households/{$household->_id}");

        $response->assertNotFound();
    });

    it('returns 404 for invalid id', function () {
        $response = $this->deleteJson('/api/households/nonexistent-id');

        $response->assertNotFound();
    });
});
