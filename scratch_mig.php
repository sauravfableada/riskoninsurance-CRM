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
        
        // Let's refine data vs alter
        // Some backfill scripts are pure data.
        if (preg_match('/(backfill_|sync_|populate_|merge_)/', $file)) {
            $type = 'data';
        }
        
        // Some specific files are just inserts
        if ($file === '2026_03_12_054852_add_stripe_keys_to_settings_table.php') {
            $type = 'data';
        }
        if ($file === '2026_03_12_055249_add_stripe_mode_to_settings_table.php') {
            $type = 'data';
        }
        if ($file === '2026_03_16_064618_update_lead_status_enum_values.php') {
            $type = 'alter';
        }
        
        $migrations[] = [
            'file' => $file,
            'type' => $type
        ];
    }
}

// Print classification for review
foreach ($migrations as $m) {
    echo str_pad($m['type'], 10) . " - " . $m['file'] . "\n";
}
