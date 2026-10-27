<?php
$dir = __DIR__ . '/resources/views';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

$count = 0;
foreach ($iterator as $file) {
    if ($file->isFile() && pathinfo($file->getFilename(), PATHINFO_EXTENSION) === 'php') {
        $path = $file->getPathname();
        $content = file_get_contents($path);
        
        $pattern = "/url\(\(env\('PUBLIC_PATH'\) \? rtrim\(env\('PUBLIC_PATH'\), '\/'\) \. '\/' : ''\) \. (.*?)\)/";
        $replacement = "asset($1)";
        
        $newContent = preg_replace($pattern, $replacement, $content, -1, $replacements);
        
        if ($replacements > 0) {
            file_put_contents($path, $newContent);
            $count += $replacements;
            echo "Updated $path ($replacements replacements)\n";
        }
    }
}
echo "Total replacements: $count\n";
