<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Mock FS constants
if (!defined('FS_FOLDER')) {
    define('FS_FOLDER', realpath(__DIR__ . '/..'));
}
if (!defined('APP_PATH')) {
    define('APP_PATH', FS_FOLDER);
}

// Load config
if (file_exists(APP_PATH . '/config.php')) {
    require_once APP_PATH . '/config.php';
}

use Modules\Trading\Model\Manufacturer;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Kernel as FSKernel;

try {
    echo "Initializing Kernel...\n";
    FSKernel::init();
    
    echo "Initializing database...\n";
    $db = new DataBase();
    if (!$db->connect()) {
        die("Connection failed\n");
    }

    echo "Instantiating Manufacturer...\n";
    $m = new Manufacturer();
    
    echo "Loading ACME...\n";
    if (!$m->loadFromCode('ACME')) {
        echo "ACME not found, creating new one...\n";
        $m->codfabricante = 'ACME';
        $m->nombre = 'Acme Corp';
    } else {
        echo "Found ACME: " . $m->nombre . "\n";
    }

    echo "Updating name...\n";
    $m->nombre = 'Acme Corporation ' . time();

    echo "Performing save()...\n";
    if ($m->save()) {
        echo "Save successful!\n";
    } else {
        echo "Save failed.\n";
    }

} catch (\Throwable $e) {
    echo "CRASH: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
