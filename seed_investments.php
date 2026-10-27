<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Seed Investment Types
$types = [
    ['type_name' => 'Equity', 'category' => 'Mutual Fund'],
    ['type_name' => 'Debt', 'category' => 'Mutual Fund'],
    ['type_name' => 'Hybrid', 'category' => 'Mutual Fund'],
    ['type_name' => 'Fixed Deposit', 'category' => 'Bank'],
    ['type_name' => 'Savings Account', 'category' => 'Bank'],
    ['type_name' => 'NSC', 'category' => 'Post Office'],
    ['type_name' => 'PPF', 'category' => 'Post Office'],
    ['type_name' => 'Gold', 'category' => 'Other Investment'],
    ['type_name' => 'Real Estate', 'category' => 'Other Investment'],
];

foreach ($types as $type) {
    DB::table('investment_types')->insert(array_merge($type, ['status' => 'active', 'created_at' => now(), 'updated_at' => now()]));
}

// Seed Investment Transaction Types
$transTypes = [
    ['name' => 'Purchase', 'type' => 'mutual_fund'],
    ['name' => 'Redemption', 'type' => 'mutual_fund'],
    ['name' => 'SIP', 'type' => 'mutual_fund'],
    ['name' => 'New Deposit', 'type' => 'bank'],
    ['name' => 'Renewal', 'type' => 'bank'],
    ['name' => 'Opening', 'type' => 'post_office'],
    ['name' => 'Buy', 'type' => 'govt_bonds'],
    ['name' => 'New Investment', 'type' => 'other'],
];

foreach ($transTypes as $tt) {
    DB::table('investment_transaction_types')->insert(array_merge($tt, ['status' => 'active', 'created_at' => now(), 'updated_at' => now()]));
}

echo "Seeding completed!" . PHP_EOL;
