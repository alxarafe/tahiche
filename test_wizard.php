<?php
require 'vendor/autoload.php';
define('FS_FOLDER', __DIR__);
require 'config.php';
$db = new FacturaScripts\Core\Base\DataBase();
$db->connect();
$tables = $db->getTables();
echo "Number of tables: " . count($tables) . "\n";
echo "Exists codigos_postales? " . ($db->tableExists('codigos_postales') ? 'yes' : 'no') . "\n";
