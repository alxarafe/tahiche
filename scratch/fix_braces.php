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
        $content = file_get_contents($file);
        
        // Match: class Name extends Base {}
        if (preg_match('/class\s+([a-zA-Z0-9_]+)\s+extends\s+([a-zA-Z0-9_\\\\]+)\s+\{\}/', $content, $matches)) {
            $className = $matches[1];
            $baseClass = $matches[2];
            
            $newContent = preg_replace(
                '/class\s+' . preg_quote($className) . '\s+extends\s+' . preg_quote($baseClass) . '\s+\{\}/',
                "class $className extends $baseClass\n{\n}",
                $content
            );
            
            file_put_contents($file, $newContent);
            echo "Fixed $file\n";
        }
    }
}
