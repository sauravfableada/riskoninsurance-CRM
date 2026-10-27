<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Investment;
use App\Models\InvestmentTransactionType;
use App\Models\InvestmentFolioNumber;
use App\Models\InvestmentType;
use App\Models\InvestmentFundName;
use App\Models\FundType;
use App\Models\Broker;
use App\Models\InvestmentMode;
use App\Models\BankMaster;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class InvestmentViewTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test that the investment view page loads and displays descriptive names.
     */
    public function test_investment_view_displays_relationship_names()
    {
        // 1. Get first existing or create related dummy models with minimal attributes
        $transactionType = InvestmentType::where('category', 'Mutual Fund')->first();
        if (!$transactionType) {
            $transactionType = InvestmentType::create([
                'type_name' => 'TEST_TX_TYPE',
                'category' => 'Mutual Fund',
                'status' => 1,
            ]);
        }

        $folioNumber = InvestmentFolioNumber::first();
        if (!$folioNumber) {
            $folioNumber = InvestmentFolioNumber::create([
                'folio_number' => 'TEST_FOLIO_123',
                'status' => 'active',
            ]);
        }

        $investmentType = InvestmentType::first();
        if (!$investmentType) {
            $investmentType = InvestmentType::create([
                'type_name' => 'TEST_INV_TYPE',
                'status' => 1,
            ]);
        }

        $fundName = InvestmentFundName::first();
        if (!$fundName) {
            $fundName = InvestmentFundName::create([
                'fund_name' => 'TEST_FUND_NAME',
                'status' => 'active',
            ]);
        }

        $fundType = FundType::first();
        if (!$fundType) {
            $fundType = FundType::create([
                'name' => 'TEST_FUND_TYPE',
                'status' => 1,
            ]);
        }

        $broker = Broker::first();
        if (!$broker) {
            $broker = Broker::create([
                'broker_name' => 'TEST_BROKER',
                'status' => 'active',
            ]);
        }

        $mode = InvestmentMode::first();
        if (!$mode) {
            $mode = InvestmentMode::create([
                'mode_name' => 'TEST_MODE',
                'status' => 'active',
            ]);
        }

        $bank = BankMaster::first();
        if (!$bank) {
            $bank = BankMaster::create([
                'name' => 'TEST_BANK',
                'status' => 1,
            ]);
        }

        // 2. Create a dummy Investment referencing these models
        $investment = Investment::create([
            'transaction_date' => now(),
            'first_name' => 'TestFirstName',
            'last_name' => 'TestLastName',
            'mobile_number' => '1234567890',
            'family_group_id' => 1,
            'investment_holder_id' => 1,
            'investment_type_main' => 'mutual_fund',
            'mf_transaction_type_id' => $transactionType->id,
            'mf_folio_number_id' => $folioNumber->id,
            'mf_investment_type_id' => $investmentType->id,
            'mf_fund_name_id' => $fundName->id,
            'mf_fund_type_id' => $fundType->id,
            'mf_broker_id' => $broker->id,
            'mf_mode_id' => $mode->id,
            'mf_payment_bank_id' => $bank->id,
        ]);

        // 3. Make GET request to the show route without middleware
        $response = $this->withoutMiddleware()
            ->get(route('investment.show', $investment->id));

        // 4. Assert successful response and that the descriptive names are present
        $response->assertStatus(200);
        $response->assertSee(e($transactionType->type_name));
        $response->assertSee(e($folioNumber->folio_number));
        $response->assertSee(e($investmentType->type_name));
        $response->assertSee(e($fundName->fund_name));
        $response->assertSee(e($fundType->name));
        $response->assertSee(e($broker->broker_name));
        $response->assertSee(e($mode->mode_name));
        $response->assertSee(e($bank->name));
    }
}
