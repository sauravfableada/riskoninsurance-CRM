<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$models = ['Lead', 'Customer', 'Task', 'Project', 'Meeting', 'Deal', 'Invoice', 'SupportTicket', 'Product', 'Service', 'User', 'Pipeline'];
foreach ($models as $model) {
    echo $model . ': ' . implode(', ', (new ('App\\Models\\'.$model))->getFillable()) . PHP_EOL;
}
