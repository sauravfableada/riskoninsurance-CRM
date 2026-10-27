<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Agent;
use App\Models\Lead;
use App\Models\TourPackage;
use App\Models\Currency;
use App\Models\Country;
use App\Models\City;
use App\Models\Hotel;
use App\Models\Supplier;
use App\Models\RoomCategory;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure a Currency exists
        $currency = Currency::first() ?? Currency::create([
            'code' => 'INR',
            'name' => 'Indian Rupee',
            'symbol' => '₹',
            'is_active' => true
        ]);

        // 2. Ensure a Country and City exist
        $country = Country::first() ?? Country::create(['name' => 'India', 'code' => 'IN']);
        $city = City::first() ?? City::create(['name' => 'Mumbai', 'country_id' => $country->id]);

        // 3. Create active Customers
        if (Customer::count() == 0) {
            Customer::create([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '9876543210',
                'is_active' => true
            ]);
            Customer::create([
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'phone' => '1234567890',
                'is_active' => true
            ]);
        }

        // 4. Create Agents
        if (Agent::count() == 0) {
            Agent::create([
                'name' => 'Global Travels',
                'email' => 'agents@global.com',
                'phone' => '1122334455',
                'is_active' => true
            ]);
            Agent::create([
                'name' => 'Local Expert',
                'email' => 'local@expert.com',
                'phone' => '9988776655',
                'is_active' => true
            ]);
        }

        // 5. Ensure a Lead exists
        if (Lead::count() == 0) {
            Lead::create([
                'name' => 'Initial Test Lead',
                'email' => 'lead@test.com',
                'phone' => '5556667777',
                'status' => 'new'
            ]);
        }

        // 6. Ensure a Room Category exists
        $roomCat = RoomCategory::first() ?? RoomCategory::create(['name' => 'Deluxe Room', 'is_active' => true]);

        // 7. Create Hotels
        if (Hotel::count() == 0) {
            Hotel::create([
                'name' => 'The Grand Jaipur Palace',
                'city_id' => $city->id,
                'email' => 'grand@jaipur.com',
                'phone' => '7778889990',
                'is_active' => true
            ]);
        }

        // 8. Create Suppliers
        if (Supplier::count() == 0) {
            Supplier::create([
                'name' => 'Rajasthan Travels Co.',
                'email' => 'contact@rajasthantravels.com',
                'phone' => '1122334455',
                'is_active' => true
            ]);
        }

        // 9. Ensure a Tour Package exists
        if (TourPackage::count() == 0) {
            TourPackage::create([
                'name' => 'Grand Rajasthan Tour',
                'duration_days' => 7,
                'duration_nights' => 6,
                'price' => 45000.00,
                'is_active' => true
            ]);
        }
    }
}
