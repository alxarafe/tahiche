<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

/**
 * This file is part of Tahiche
 * Copyright (C) 2017-2024 Tahiche Team <tahiche@alxarafe.com>
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
 */

// 1. Carga del Autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    file_put_contents(__DIR__ . '/../debug_error.log', "Error [$errno]: $errstr in $errfile on line $errline\n", FILE_APPEND);
    return false; // let the default error handler run too
});
set_exception_handler(function($e) {
    file_put_contents(__DIR__ . '/../debug_error.log', "Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
    throw $e; // Re-throw to ensure standard output
});

// 2. Definición de constantes de rutas globales
define('APP_PATH', dirname(__DIR__));
define('BASE_PATH', __DIR__);
define('FS_FOLDER', APP_PATH);
define('folder', APP_PATH);

// 3. Captura de la URL de la petición
$url = isset($argv[1]) && $argv[1] === '-cron' ?
    '/cron' :
    parse_url($_SERVER["REQUEST_URI"] ?? '', PHP_URL_PATH) ?? '';

// 4. Arranque del Kernel Hexagonal
$kernel = new \Tahiche\Infrastructure\Http\Kernel();
$kernel->handle($url);
