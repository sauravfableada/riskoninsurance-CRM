<?php

$dir = __DIR__ . '/database/migrations';
$files = scandir($dir);
$migrations = [];

foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $content = file_get_contents($dir . '/' . $file);
        
        $type = 'unknown';
        if (strpos($file, 'create_') !== false) {
            $type = 'create';
        } elseif (preg_match('/(add_|drop_|update_|rename_|sync_|backfill_|merge_|populate_|ensure_)/', $file)) {
            if (strpos($content, 'Schema::table') !== false || strpos($content, 'Schema::drop') !== false || strpos($content, 'Schema::rename') !== false) {
                $type = 'alter';
            } else {
                $type = 'data';
            }
        }
        
        if ($type === 'unknown' && strpos($content, 'Schema::create') !== false) {
            $type = 'create';
        }
        if ($type === 'unknown' && strpos($content, 'Schema::table') !== false) {
            $type = 'alter';
        }
        
        if (preg_match('/(backfill_|sync_|populate_|merge_)/', $file)) {
            $type = 'data';
        }
        
        if ($file === '2026_03_12_054852_add_stripe_keys_to_settings_table.php') {
            $type = 'data';
        }
        if ($file === '2026_03_12_055249_add_stripe_mode_to_settings_table.php') {
            $type = 'data';
        }
        if ($file === '2026_03_16_064618_update_lead_status_enum_values.php') {
            $type = 'alter';
        }

        if ($type === 'unknown') {
            echo "Warning: Unknown type for $file\n";
            $type = 'alter'; // default safe fallback
        }
        
        $migrations[] = [
            'file' => $file,
            'type' => $type,
            'original_timestamp' => substr($file, 0, 17)
        ];
    }
}

// Group them
$creates = array_filter($migrations, fn($m) => $m['type'] === 'create');
$alters = array_filter($migrations, fn($m) => $m['type'] === 'alter');
$data = array_filter($migrations, fn($m) => $m['type'] === 'data');

// Ensure sorting by original filename to preserve dependency order within group
usort($creates, fn($a, $b) => strcmp($a['file'], $b['file']));
usort($alters, fn($a, $b) => strcmp($a['file'], $b['file']));
usort($data, fn($a, $b) => strcmp($a['file'], $b['file']));

$all = [
    ['prefix' => '2026_01_01_', 'items' => $creates],
    ['prefix' => '2026_02_01_', 'items' => $alters],
    ['prefix' => '2026_03_01_', 'items' => $data],
];

foreach ($all as $group) {
    $counter = 1;
    foreach ($group['items'] as $item) {
        $oldName = $item['file'];
        // Remove the original timestamp 2026_03_12_054852_
        // wait, earlier ones are 2014_10_12_000000_
        $nameWithoutTimestamp = substr($oldName, 18); 
        // fallback in case some don't have exactly 17 chars + underscore
        if (!preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_/', $oldName)) {
            // just use original if we can't figure it out easily (shouldn't happen in standard laravel)
            $nameWithoutTimestamp = $oldName;
        }

        $newName = $group['prefix'] . sprintf('%06d', $counter) . '_' . $nameWithoutTimestamp;
        
        rename($dir . '/' . $oldName, $dir . '/' . $newName);
        echo "Renamed $oldName to $newName\n";
        $counter++;
    }
}

echo "Done renaming migrations.\n";
