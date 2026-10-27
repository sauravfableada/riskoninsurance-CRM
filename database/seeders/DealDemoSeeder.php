<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\Status;
use App\Models\User;
use App\Models\Country;
use App\Models\City;
use Illuminate\Database\Seeder;

class DealDemoSeeder extends Seeder
{
    public function run(): void
    {
        $dealStatuses = [
            ['name' => 'New', 'color' => '#3B82F6'],
            ['name' => 'Qualified', 'color' => '#6366F1'],
            ['name' => 'Proposal', 'color' => '#F59E0B'],
            ['name' => 'Negotiation', 'color' => '#8B5CF6'],
            ['name' => 'Won', 'color' => '#10B981'],
            ['name' => 'Lost', 'color' => '#EF4444'],
        ];

        foreach ($dealStatuses as $status) {
            Status::firstOrCreate(
                ['name' => $status['name'], 'type' => 'deal'],
                ['color' => $status['color'], 'is_active' => true]
            );
        }

        $country = Country::first();
        $city = $country ? City::where('country_id', $country->id)->first() : null;

        $customers = [
            ['name' => 'Aarav Mehta', 'email' => 'aarav@example.com', 'phone' => '9876543210', 'type' => 'Individual'],
            ['name' => 'Isha Kapoor', 'email' => 'isha@example.com', 'phone' => '9123456780', 'type' => 'Individual'],
            ['name' => 'Kunal Shah', 'email' => 'kunal@example.com', 'phone' => '9988776655', 'type' => 'Corporate'],
            ['name' => 'Neha Rao', 'email' => 'neha@example.com', 'phone' => '9090909090', 'type' => 'Individual'],
            ['name' => 'Vikram Desai', 'email' => 'vikram@example.com', 'phone' => '9898989898', 'type' => 'Corporate'],
        ];

        foreach ($customers as $cust) {
            Customer::firstOrCreate(
                ['email' => $cust['email']],
                [
                    'name' => $cust['name'],
                    'phone' => $cust['phone'],
                    'type' => $cust['type'],
                    'country_id' => $country?->id,
                    'city_id' => $city?->id,
                    'is_active' => true,
                ]
            );
        }

        $currency = Currency::firstOrCreate(
            ['code' => 'INR'],
            ['name' => 'Indian Rupee', 'symbol' => '₹', 'exchange_rate' => 1, 'is_default' => true, 'is_active' => true]
        );

        $users = User::orderBy('id')->get();
        if ($users->isEmpty()) {
            $this->command->warn('No users found. Deals were not created because assigned_user_id is required.');
            return;
        }

        $statusIds = Status::where('type', 'deal')->pluck('id')->all();
        $customerIds = Customer::pluck('id')->all();

        $dealTitles = [
            'Goa Family Getaway',
            'Dubai Luxury Escape',
            'Bali Honeymoon Special',
            'Kerala Backwaters Retreat',
            'Singapore City Break',
        ];

        foreach ($dealTitles as $i => $title) {
            $dealCustomerId = $customerIds[$i % count($customerIds)];
            $dealStatusId = $statusIds[$i % count($statusIds)];
            $assignedUserId = $users[$i % $users->count()]->id;

            Deal::updateOrCreate(
                ['title' => $title, 'customer_id' => $dealCustomerId],
                [
                    'amount' => 50000 + ($i * 15000),
                    'currency_id' => $currency->id,
                    'status_id' => $dealStatusId,
                    'expected_close_date' => now()->addDays(7 + ($i * 5))->toDateString(),
                    'assigned_user_id' => $assignedUserId,
                ]
            );
        }

        $this->command->info('Deal demo data seeded successfully!');
    }
}
