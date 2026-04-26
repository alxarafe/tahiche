<?php
require_once __DIR__ . '/vendor/autoload.php';

if (!defined('FS_FOLDER')) {
    define('FS_FOLDER', __DIR__);
}

use FacturaScripts\Core\Internal\ClassResolver;

ClassResolver::register();

$legacyClass = "FacturaScripts\\Dinamic\\Model\\Impuesto";
$realClass = ClassResolver::getRealClass($legacyClass);

echo "Legacy: $legacyClass\n";
echo "Real:   " . ($realClass ?? 'NULL') . "\n";

$legacyClass2 = "FacturaScripts\\Dinamic\\Model\\CodeModel";
$realClass2 = ClassResolver::getRealClass($legacyClass2);
echo "Legacy: $legacyClass2\n";
echo "Real:   " . ($realClass2 ?? 'NULL') . "\n";
