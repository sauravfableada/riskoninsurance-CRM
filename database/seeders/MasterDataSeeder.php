<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Country;
use App\Models\City;
use App\Models\Currency;
use App\Models\LeadSource;
use App\Models\Stage;
use App\Models\TravelType;
use App\Models\TransportType;
use App\Models\RoomCategory;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Countries
        $countries = [
            ['name' => 'India', 'iso_code' => 'IND'],
            ['name' => 'United Arab Emirates', 'iso_code' => 'UAE'],
            ['name' => 'United States', 'iso_code' => 'USA'],
            ['name' => 'United Kingdom', 'iso_code' => 'GBR'],
            ['name' => 'Singapore', 'iso_code' => 'SGP'],
            ['name' => 'Thailand', 'iso_code' => 'THA'],
            ['name' => 'Australia', 'iso_code' => 'AUS'],
        ];
        
        foreach ($countries as $c) {
            Country::firstOrCreate(['iso_code' => $c['iso_code']], $c);
        }

        // 2. Cities (Linked to India and UAE as examples)
        $india = Country::where('iso_code', 'IND')->first();
        $uae = Country::where('iso_code', 'UAE')->first();

        if ($india) {
            $indianCities = ['Mumbai', 'Delhi', 'Bangalore', 'Goa', 'Ahmedabad', 'Jaipur', 'Kochi'];
            foreach ($indianCities as $city) {
                City::firstOrCreate(['name' => $city, 'country_id' => $india->id]);
            }
        }

        if ($uae) {
            $uaeCities = ['Dubai', 'Abu Dhabi', 'Sharjah'];
            foreach ($uaeCities as $city) {
                City::firstOrCreate(['name' => $city, 'country_id' => $uae->id]);
            }
        }

        // 3. Currencies
        $currencies = [
            ['name' => 'Indian Rupee', 'code' => 'INR', 'symbol' => '₹', 'exchange_rate' => 1.00],
            ['name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$', 'exchange_rate' => 83.50],
            ['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€', 'exchange_rate' => 90.20],
            ['name' => 'British Pound', 'code' => 'GBP', 'symbol' => '£', 'exchange_rate' => 105.40],
            ['name' => 'UAE Dirham', 'code' => 'AED', 'symbol' => 'د.إ', 'exchange_rate' => 22.70],
        ];

        foreach ($currencies as $curr) {
            Currency::firstOrCreate(['code' => $curr['code']], $curr);
        }

        // 4. Lead Sources (Where did the inquiry come from?)
        $sources = ['Website Form', 'Google Ads', 'Facebook Ads', 'Instagram', 'Referral', 'Walk-in', 'B2B Partner'];
        foreach ($sources as $source) {
            LeadSource::firstOrCreate(['name' => $source]);
        }

        // 5. Stages (Pipeline progression)
        $stages = [
            ['name' => 'New', 'sort_order' => 1, 'is_default' => true],
            ['name' => 'Contacted', 'sort_order' => 2, 'is_default' => false],
            ['name' => 'Proposal Sent', 'sort_order' => 3, 'is_default' => false],
            ['name' => 'Negotiation', 'sort_order' => 4, 'is_default' => false],
            ['name' => 'Won', 'sort_order' => 5, 'is_default' => false],
            ['name' => 'Lost', 'sort_order' => 6, 'is_default' => false],
        ];

        foreach ($stages as $stage) {
            Stage::firstOrCreate(['name' => $stage['name']], $stage);
        }

        // 6. Travel Types (Why are they traveling?)
        $travelTypes = ['Leisure', 'Business / Corporate', 'Honeymoon', 'Family Tour', 'Group Tour', 'Adventure / Trekking', 'Pilgrimage'];
        foreach ($travelTypes as $type) {
            TravelType::firstOrCreate(['name' => $type]);
        }

        // 7. Transport Types
        $transports = ['Flight', 'Private Car', 'Shared Coach / Bus', 'Train', 'Cruise', 'Ferry'];
        foreach ($transports as $trans) {
            TransportType::firstOrCreate(['name' => $trans]);
        }

        // 8. Room Categories
        $rooms = ['Standard', 'Deluxe', 'Super Deluxe', 'Executive Suite', 'Presidential Suite', 'Villa', 'Water Villa'];
        foreach ($rooms as $room) {
            RoomCategory::firstOrCreate(['name' => $room]);
        }

        $this->command->info('Master Data seeded successfully!');
        
        $this->command->table(
            ['Module', 'Records Seeded'],
            [
                ['Countries', Country::count()],
                ['Cities', City::count()],
                ['Currencies', Currency::count()],
                ['Lead Sources', LeadSource::count()],
                ['Stages', Stage::count()],
                ['Travel Types', TravelType::count()],
                ['Transport Types', TransportType::count()],
                ['Room Categories', RoomCategory::count()],
            ]
        );
    }
}
