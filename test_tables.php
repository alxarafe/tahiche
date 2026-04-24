<?php
require 'vendor/autoload.php';
define('FS_FOLDER', __DIR__);
require 'config.php';
$db = new FacturaScripts\Core\Base\DataBase();
$db->connect();
$tables = $db->getTables();
var_dump(in_array('codigos_postales', $tables));
