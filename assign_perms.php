<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::find(37);
if ($user) {
    $user->givePermissionTo(Spatie\Permission\Models\Permission::findOrCreate('view_policies', 'web'));
    $user->givePermissionTo(Spatie\Permission\Models\Permission::findOrCreate('create_policies', 'web'));
    $user->givePermissionTo(Spatie\Permission\Models\Permission::findOrCreate('edit_policies', 'web'));
    $user->givePermissionTo(Spatie\Permission\Models\Permission::findOrCreate('delete_policies', 'web'));
    echo 'Permissions added successfully.';
}
