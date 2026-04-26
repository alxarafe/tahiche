<?php

declare(strict_types=1);

namespace Tahiche\Infrastructure\Bridge;

use FacturaScripts\Core\Plugins;

/**
 * Trait para proporcionar compatibilidad retroactiva con plugins legacy de FacturaScripts.
 * Captura las llamadas de la API antigua (XMLView) y las traduce a la estructura moderna Hexagonal.
 */
trait LegacyBridgeTrait
{
    protected array $legacyTabs = [];
    protected array $legacyButtons = [];
    protected array $loadedLegacyExtensions = [];

    // Propiedad esperada por los plugins legacy para comprobaciones de permisos
    public ?\FacturaScripts\Core\Model\User $user = null;

    /**
     * Busca y ejecuta las extensiones de plugins para un controlador legacy específico.
     */
    protected function applyLegacyExtensions(string $legacyControllerName): void
    {
        // Obtener el usuario real de la sesión legacy
        $this->user = $this->loadCurrentUser();

        $plugins = Plugins::enabled();

        foreach ($plugins as $plugin) {
            $className = "\\FacturaScripts\\Plugins\\{$plugin}\\Extension\\Controller\\{$legacyControllerName}";

            if (class_exists($className)) {
                $extension = new $className();
                $this->loadedLegacyExtensions[] = $extension;

                // El estándar de FS es que las extensiones sobreescriban createViews()
                if (method_exists($extension, 'createViews')) {
                    // Usamos Reflection porque a menudo estos métodos son protected en los plugins
                    $reflection = new \ReflectionMethod($extension, 'createViews');
                    $reflection->setAccessible(true);

                    $closure = $reflection->invoke($extension);
                    if ($closure instanceof \Closure) {
                        // Vinculamos el closure a ESTE controlador para atrapar las llamadas a $this
                        $boundClosure = $closure->bindTo($this, static::class);
                        if ($boundClosure) {
                            $boundClosure();
                        }
                    }
                }
            }
        }
    }

    /**
     * Carga el usuario real de la sesión legacy usando las cookies de FacturaScripts.
     */
    private function loadCurrentUser(): \FacturaScripts\Core\Model\User
    {
        $cookieNick = $_COOKIE['fsNick'] ?? '';
        if (!empty($cookieNick)) {
            $dinUserClass = '\\FacturaScripts\\Core\\Model\\User';
            if (class_exists($dinUserClass)) {
                $user = new $dinUserClass();
                if ($user->load($cookieNick)) {
                    return $user;
                }
            }
        }

        // Fallback: usuario vacío con permisos básicos
        $user = new \FacturaScripts\Core\Model\User();
        $user->admin = true;
        return $user;
    }

    /**
     * Atrapa las llamadas a métodos que no existen en el controlador moderno.
     * Si el método existe en alguna de las extensiones legacy cargadas, lo ejecuta.
     */
    public function __call(string $name, array $arguments)
    {
        foreach ($this->loadedLegacyExtensions as $extension) {
            if (method_exists($extension, $name)) {
                $reflection = new \ReflectionMethod($extension, $name);
                $reflection->setAccessible(true);
                $result = $reflection->invokeArgs($extension, $arguments);

                // Si la extensión devuelve un Closure (típico en FS), lo vinculamos a nuestro controlador
                if ($result instanceof \Closure) {
                    $boundClosure = $result->bindTo($this, static::class);
                    return $boundClosure ? $boundClosure() : null;
                }

                return $result;
            }
        }

        throw new \Error("Call to undefined method " . static::class . "::" . $name . "()");
    }

    // --- MÉTODOS FACADE PARA ATRAPAR LAS LLAMADAS DEL PLUGIN ---

    public function tab(string $name)
    {
        // Devolvemos un objeto anónimo para absorber llamadas encadenadas sin fallar
        return new class {
            public function disableColumn()
            {
                return $this;
            }
            public function enableColumn()
            {
                return $this;
            }
        };
    }

    public function addListView(string $viewName, string $model, string $tabId, string $icon = '')
    {
        // Registramos la intención del plugin de crear una pestaña de lista
        $this->legacyTabs[] = [
            'id' => $tabId,
            'label' => $model,
            'icon' => $icon,
            'viewName' => $viewName,
        ];

        // Objeto absorbente para los métodos de filtrado y ordenación
        return new class {
            public function addOrderBy()
            {
                return $this;
            }
            public function addSearchFields()
            {
                return $this;
            }
            public function disableColumn()
            {
                return $this;
            }
            public function setSettings()
            {
                return $this;
            }
            public function addFilterPeriod()
            {
                return $this;
            }
            public function addFilterNumber()
            {
                return $this;
            }
            public function addFilterSelect()
            {
                return $this;
            }
        };
    }

    public function addButton(string $viewName, array $options)
    {
        // Registramos la intención de añadir un botón superior
        $this->legacyButtons[] = $options;
        return $this;
    }

    public function listView(string $viewName)
    {
        // En FS, listView() recupera una vista existente para añadirle cosas.
        // Por compatibilidad, devolvemos un objeto absorbente.
        return $this->addListView($viewName, '', '');
    }

    // --- UTILITIES PARA EL CONTROLADOR HEXAGONAL ---

    protected function transLegacy(string $text): string
    {
        return \FacturaScripts\Core\Tools::trans($text);
    }

    protected function countLegacyRecords(string $modelClass, string $foreignKey, $id): int
    {
        return (new $modelClass())->count([\FacturaScripts\Core\Where::eq($foreignKey, $id)]);
    }

    protected function getLegacyModelCount(string $modelName, string $foreignKey, $id): int
    {
        $modelClass = \FacturaScripts\Core\Internal\ClassResolver::getRealClass("\\FacturaScripts\\Dinamic\\Model\\" . $modelName) ?? "\\FacturaScripts\\Dinamic\\Model\\" . $modelName;
        if (class_exists($modelClass)) {
            return $this->countLegacyRecords($modelClass, $foreignKey, $id);
        }
        return 0;
    }

    protected function getLegacyRelatedRecords(string $modelClass, string $foreignKey, $foreignValue): array
    {
        return (new $modelClass())->all([\FacturaScripts\Core\Where::eq($foreignKey, $foreignValue)]);
    }
}
