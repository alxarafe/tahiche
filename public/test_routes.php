<?php
require __DIR__ . '/../vendor/autoload.php';
define('BASE_PATH', __DIR__);
define('APP_PATH', dirname(BASE_PATH));
$routes = \Alxarafe\Infrastructure\Http\Routes::getAllRoutes();
var_dump($routes['Controller']['Sales']['SalesInvoices'] ?? 'NOT FOUND');
