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
use FacturaScripts\Core\Html;
use FacturaScripts\Core\Lib\MenuManager;
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Dinamic\Model\User as DinUser;

/**
 * Kernel de la Arquitectura Hexagonal (Strangler Fig Router).
 * Se encarga de inicializar el entorno y delegar en el Core original de FacturaScripts
 * o en los nuevos controladores según corresponda.
 *
 * Los nuevos ResourceControllers generan solo el contenido interno de la página
 * y el Kernel los envuelve en el layout legacy (MenuTemplate) para mantener
 * la coherencia visual, el menú de navegación y la autenticación del ERP.
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

    /**
     * Map of legacy controllers to modern module controllers
     */
    private const LEGACY_ROUTE_MAP = [
        '/ListPais'       => ['module' => 'Trading', 'controller' => 'Countries'],
        '/ListFabricante' => ['module' => 'Trading', 'controller' => 'Manufacturers'],
        '/ListAlmacen'    => ['module' => 'Trading', 'controller' => 'Warehouses'],
        '/ListImpuesto'   => ['module' => 'Trading', 'controller' => 'Taxes'],
    ];

    private function run(string $url): void
    {
        // Inicializamos plugins (excepto en rutas de despliegue)
        if ($url !== '/deploy') {
            Plugins::init();
        }

        // Dispatcher Estrangulador: Si hay parámetro 'module', delegamos en la nueva arquitectura
        if (isset($_GET['module'])) {
            $this->dispatchModule($_GET['module'], $_GET['controller'] ?? '');
            return;
        }

        // Reconstrucción de la ruta legacy para FSKernel
        $legacyRoute = str_replace('/index.php', '', $url);
        if (empty($legacyRoute) || $legacyRoute === '/') {
            $legacyRoute = isset($_GET['controller']) ? '/' . $_GET['controller'] : '/';
        }

        // Intercept legacy routes and redirect to new architecture
        if (isset(self::LEGACY_ROUTE_MAP[$legacyRoute])) {
            $mapped = self::LEGACY_ROUTE_MAP[$legacyRoute];
            // Fake the GET parameters so controllers that rely on them work
            $_GET['module'] = $mapped['module'];
            $_GET['controller'] = $mapped['controller'];
            $this->dispatchModule($mapped['module'], $mapped['controller']);
            return;
        }

        FSKernel::run($legacyRoute);
    }

    /**
     * Despacha peticiones a controladores modernos en Modules/.
     * URL: index.php?module=Trading&controller=Manufacturers
     *
     * 1. Autentica al usuario reutilizando las cookies del legacy
     * 2. Resuelve e instancia el ResourceController
     * 3. Captura su output (contenido interno sin layout)
     * 4. Lo envuelve con el layout Twig del legacy (menú, CSS, JS)
     */
    private function dispatchModule(string $module, string $controller): void
    {
        $module = ucfirst($module);
        $controller = ucfirst($controller);

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

        // 1. Autenticación: reutilizamos las cookies del legacy
        $user = $this->authenticateUser();
        if (!$user) {
            // Sin usuario autenticado → redirigir al login legacy
            header('Location: ' . Tools::config('route', '') . '/login');
            return;
        }

        // 2. Instanciar y ejecutar el ResourceController
        $instance = new $className();

        // 3. Capturar el output (solo el contenido interno, sin layout HTML)
        ob_start();
        $instance->index();
        $resourceContent = ob_get_clean();

        // 4. Renderizar dentro del layout legacy con menú
        $this->renderWithLegacyLayout($instance, $resourceContent, $user);
    }

    /**
     * Autentica al usuario reutilizando las cookies del sistema legacy.
     * Devuelve el User si la autenticación es exitosa, false en caso contrario.
     */
    private function authenticateUser(): ?User
    {
        $cookieNick = $_COOKIE['fsNick'] ?? '';
        if (empty($cookieNick)) {
            return null;
        }

        $user = new DinUser();
        if (false === $user->load($cookieNick)) {
            return null;
        }

        if (false === $user->enabled) {
            return null;
        }

        $logKey = $_COOKIE['fsLogkey'] ?? '';
        if (false === $user->verifyLogkey($logKey)) {
            return null;
        }

        // Actualizar actividad si corresponde
        if (time() - strtotime($user->lastactivity) > User::UPDATE_ACTIVITY_PERIOD) {
            $ip = Session::getClientIp();
            $browser = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $user->updateActivity($ip, $browser);
            $user->save();
        }

        // Establecer usuario en la sesión del legacy
        Session::set('user', $user);

        return $user;
    }

    /**
     * Envuelve el contenido del ResourceController con el layout legacy Twig.
     * Usa la plantilla puente ResourceBridge.html.twig que extiende MenuTemplate.
     */
    private function renderWithLegacyLayout(ResourceController $controller, string $resourceContent, User $user): void
    {
        $pageData = $controller->getPageData();

        // Construir el MenuManager del legacy y marcar la página activa
        $menuManager = MenuManager::init()->selectPage($pageData);

        // Crear un objeto adaptador mínimo para $fsc (requerido por MenuTemplate)
        $fsc = new class ($user, $pageData) {
            public User $user;
            public $empresa;
            public string $title;
            public $multiRequestProtection;

            public function __construct(User $user, array $pageData)
            {
                $this->user = $user;
                $this->empresa = Empresas::default();
                $this->title = (new \FacturaScripts\Core\Translator())->trans($pageData['title'] ?? '');
                $this->multiRequestProtection = new \FacturaScripts\Dinamic\Lib\MultiRequestProtection();
            }

            public function url(): string
            {
                return '';
            }
        };

        // Renderizar con Twig: layout legacy + contenido del ResourceController
        $html = Html::render('ResourceBridge.html.twig', [
            'controllerName' => $pageData['name'] ?? '',
            'fsc'            => $fsc,
            'menuManager'    => $menuManager,
            'template'       => 'ResourceBridge.html.twig',
            'resourceContent' => $resourceContent,
        ]);

        echo $html;
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
