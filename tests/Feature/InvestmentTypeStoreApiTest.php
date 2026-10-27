<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\InvestmentType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class InvestmentTypeStoreApiTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test that storing a new investment type via API returns the created record details in JSON.
     */
    public function test_store_investment_type_returns_created_record_details()
    {
        $response = $this->withoutMiddleware()
            ->postJson(route('masters.investment_type.store'), [
                'name' => 'TEST_MUTUAL_FUND_TYPE_XYZ',
                'type' => 'Mutual Fund',
                'status' => 1,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'message' => 'Investment type added successfully',
            'data' => [
                'type_name' => 'TEST_MUTUAL_FUND_TYPE_XYZ',
                'category' => 'Mutual Fund',
                'status' => 1,
            ]
        ]);

        $this->assertDatabaseHas('investment_types', [
            'type_name' => 'TEST_MUTUAL_FUND_TYPE_XYZ',
            'category' => 'Mutual Fund',
            'status' => 1,
        ]);
    }

    /**
     * Test that validation checks work (e.g. duplicate name checks).
     */
    public function test_store_investment_type_validates_duplicate_names()
    {
        // Pre-create an investment type
        InvestmentType::create([
            'type_name' => 'DUPLICATE_TYPE_TEST',
            'category' => 'Mutual Fund',
            'status' => 1,
        ]);

        // Attempt to store it again
        $response = $this->withoutMiddleware()
            ->postJson(route('masters.investment_type.store'), [
                'name' => 'DUPLICATE_TYPE_TEST',
                'type' => 'Mutual Fund',
                'status' => 1,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => false,
            'message' => 'Investment type name already exists',
        ]);
    }
}
