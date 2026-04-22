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

use Tahiche\Core\Base\DataBase;
use Tahiche\Core\Cache;
use Tahiche\Core\Kernel;
use Tahiche\Core\Plugins;

define("FS_FOLDER", getcwd());

require_once __DIR__ . '/../vendor/autoload.php';

$config = FS_FOLDER . '/config.php';
if (!file_exists($config)) {
    die($config . " not found!\n");
}

require_once $config;

// conectamos a la base de datos
$db = new DataBase();
$db->connect();

// limpiamos la caché
Cache::clear();

// iniciamos el kernel
Kernel::init();

// inicializamos los plugins
Plugins::init();

// leemos la lista de plugins a instalar
$listPath = __DIR__ . '/Plugins/install-plugins.txt';
if (file_exists($listPath)) {
    $content = file_get_contents($listPath);
    $list = array_map('trim', explode(',', $content));
    foreach ($list as $plugin) {
        if (empty($plugin) || Plugins::isEnabled($plugin)) {
            continue;
        }

        // activamos el plugin
        if (Plugins::enable($plugin)) {
            echo '-> Plugin ' . $plugin . ' enabled.' . PHP_EOL;
            exit(0);
        }

        echo '-> Plugin ' . $plugin . ' not found.' . PHP_EOL;
        exit(2);
    }
}

// desconectamos de la base de datos
$db->close();
