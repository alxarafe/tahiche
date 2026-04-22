<?php

$dirs = [
    'Modules/Trading/Model',
    'Modules/Accounting/Model',
    'Modules/Admin/Model',
    'Modules/Crm/Model',
    'Modules/Sales/Model'
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;
    
    foreach (glob("$dir/*.php") as $file) {
        $lines = file($file);
        $newLines = [];
        $foundClass = false;
        
        foreach ($lines as $line) {
            if (preg_match('/^class\s+/', $line)) {
                $foundClass = true;
                $newLines[] = rtrim($line) . "\n";
                $newLines[] = "{\n";
                $newLines[] = "}\n";
                break;
            }
            $newLines[] = $line;
        }
        
        file_put_contents($file, implode('', $newLines));
        echo "Cleaned $file\n";
    }
}
