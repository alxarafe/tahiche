<?php

namespace Tahiche\Infrastructure\Http;

use FacturaScripts\Core\Tools;
use FacturaScripts\Core\CrashReport;
use FacturaScripts\Core\Kernel as FSKernel;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\WorkQueue;
use FacturaScripts\Core\Telemetry;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\NextCode;

/**
 * Kernel de la Arquitectura Hexagonal (Strangler Fig Router).
 * Se encarga de inicializar el entorno y delegar en el Core original de FacturaScripts
 * o en los nuevos controladores según corresponda.
 */
class Kernel
{
    public function handle(string $url): void
    {
        $this->setup();
        $this->run($url);
        $this->terminate();
    }

    private function setup(): void
    {
        // 1. Configuración del entorno de ejecución
        @set_time_limit(0);
        ignore_user_abort(true);

        $timeZone = Tools::config('timezone', 'Europe/Madrid');
        date_default_timezone_set($timeZone);

        // 2. Cargar configuración de usuario si existe (DEBE SER ANTES DE FSKernel::init)
        if (file_exists(APP_PATH . '/config.php')) {
            require_once APP_PATH . '/config.php';
        }

        // 3. Inicialización de los servicios base del Legacy Core
        CrashReport::init();
        FSKernel::init();
    }

    private function run(string $url): void
    {
        // Inicializamos plugins (excepto en rutas de despliegue)
        if ($url !== '/deploy') {
            Plugins::init();
        }

        // Dispatcher Estrangulador: Si hay parámetro 'module', delegamos en la nueva arquitectura
        if (isset($_GET['module'])) {
            $this->dispatchModule();
        } else {
            // Reconstrucción de la ruta legacy para FSKernel
            $legacyRoute = str_replace('/index.php', '', $url);
            if (empty($legacyRoute) || $legacyRoute === '/') {
                $legacyRoute = isset($_GET['controller']) ? '/' . $_GET['controller'] : '/';
            }

            FSKernel::run($legacyRoute);
        }
    }

    /**
     * Despacha peticiones a controladores modernos en Modules/.
     * URL: index.php?module=Trading&controller=Manufacturers
     */
    private function dispatchModule(): void
    {
        $module = ucfirst($_GET['module'] ?? '');
        $controller = ucfirst($_GET['controller'] ?? '');

        if (empty($module) || empty($controller)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing module or controller parameter']);
            return;
        }

        // Buscamos la clase en Modules/{Module}/Controller/{Controller}Controller
        $className = "Modules\\{$module}\\Controller\\{$controller}Controller";

        if (!class_exists($className)) {
            http_response_code(404);
            echo json_encode(['error' => "Controller not found: {$className}"]);
            return;
        }

        $instance = new $className();
        $instance->index();
    }

    private function terminate(): void
    {
        // Tareas de finalización y limpieza del Core de FacturaScripts
        // Solo ejecutamos si la base de datos está inicializada y conectada
        $db = new DataBase();
        if ($db->getEngine() && $db->connected()) {
            WorkQueue::run();
            Telemetry::init()->update();
            MiniLog::save();
            $db->close();
            NextCode::clearOld();
        }
    }
}
