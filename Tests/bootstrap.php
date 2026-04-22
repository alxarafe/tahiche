<?php
/**
 * This file is part of Tahiche
 * Copyright (C) 2017-2025 Tahiche Team <tahiche@alxarafe.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

use Tahiche\Core\Cache;
use Tahiche\Core\Kernel;
use Tahiche\Core\Plugins;
use Tahiche\Core\Tools;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Core/legacy_aliases.php';

// cargamos la configuración
define("FS_FOLDER", getcwd());
define("APP_PATH", FS_FOLDER);
define("BASE_PATH", FS_FOLDER . '/public');

$configPhp = FS_FOLDER . '/config.php';
$configJson = FS_FOLDER . '/config/config.json';

if (file_exists($configPhp)) {
    echo 'Using legacy config.php' . "\n";
    require_once $configPhp;
} elseif (file_exists($configJson)) {
    echo 'Using config/config.json via ConfigBridge' . "\n";
    \Tahiche\Core\ConfigBridge::load(FS_FOLDER);
} else {
    // Entorno CI: definir constantes mínimas desde variables de entorno
    echo 'No config found — using environment variables' . "\n";
    if (!defined('FS_DB_TYPE')) {
        define('FS_DB_TYPE', getenv('DB_CONNECTION') ?: 'mysql');
    }
    if (!defined('FS_DB_HOST')) {
        define('FS_DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
    }
    if (!defined('FS_DB_PORT')) {
        define('FS_DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
    }
    if (!defined('FS_DB_NAME')) {
        define('FS_DB_NAME', getenv('DB_DATABASE') ?: 'tahiche_test');
    }
    if (!defined('FS_DB_USER')) {
        define('FS_DB_USER', getenv('DB_USERNAME') ?: 'root');
    }
    if (!defined('FS_DB_PASS')) {
        define('FS_DB_PASS', getenv('DB_PASSWORD') ?: 'root');
    }
}

echo "\n" . '    PHP: ' . phpversion();
echo "\n" . 'DB Host: ' . (defined('FS_DB_HOST') ? FS_DB_HOST : 'N/A');
echo "\n" . 'DB User: ' . (defined('FS_DB_USER') ? FS_DB_USER : 'N/A');
echo "\n" . 'DB Name: ' . (defined('FS_DB_NAME') ? FS_DB_NAME : 'N/A') . "\n\n";

// establecemos la zona horaria
$timeZone = Tools::config('timezone', 'Europe/Madrid');
date_default_timezone_set($timeZone);

// clean cache
Cache::clear();

// iniciamos el kernel
Kernel::init();

// deploy
Plugins::deploy();
