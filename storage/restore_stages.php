<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$stages = [
    ['name' => 'New', 'sort_order' => 1, 'is_default' => false, 'is_active' => true],
    ['name' => 'Contacted', 'sort_order' => 2, 'is_default' => false, 'is_active' => true],
    ['name' => 'Proposal Sent', 'sort_order' => 3, 'is_default' => false, 'is_active' => true],
    ['name' => 'Negotiation', 'sort_order' => 4, 'is_default' => true, 'is_active' => true],
    ['name' => 'Won', 'sort_order' => 5, 'is_default' => false, 'is_active' => true],
    ['name' => 'Lost', 'sort_order' => 6, 'is_default' => false, 'is_active' => true],
];

foreach ($stages as $s) {
    $item = \App\Models\Stage::firstOrNew(['name' => $s['name']]);
    $item->fill($s);
    $item->save();
}

\App\Models\Stage::where('name', '!=', 'Negotiation')->update(['is_default' => false]);

echo "Restored\n";
