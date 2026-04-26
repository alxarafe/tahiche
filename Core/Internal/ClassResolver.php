<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 */

namespace FacturaScripts\Core\Internal;

use FacturaScripts\Core\Plugins;

/**
 * Autoloader que reemplaza la función de resolución de Dinamic.
 * Cuando alguien intenta cargar una clase de FacturaScripts\Dinamic\*
 * o FacturaScripts\Core\* que ya no existe en el Core, el ClassResolver
 * busca en los plugins activos y crea un alias transparente.
 *
 * Esto permite que los plugins legacy sigan usando los namespaces
 * originales sin modificación.
 */
final class ClassResolver
{
    /** @var bool */
    private static bool $registered = false;

    /** @var array<string, string> Cache de resoluciones ya hechas */
    private static array $resolved = [];

    /** @var bool */
    private static bool $dirty = false;

    /**
     * Registra el autoloader. Debe llamarse una sola vez, lo antes posible.
     */
    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        $cacheFile = FS_FOLDER . '/var/cache/class_resolver.php';
        if (file_exists($cacheFile)) {
            self::$resolved = require $cacheFile;
        }

        // prepend = false (al final) para que Composer resuelva todo lo normal primero
        spl_autoload_register([self::class, 'resolve'], true, false);
        self::$registered = true;

        register_shutdown_function(function () use ($cacheFile) {
            if (self::$dirty) {
                // Ensure directory exists
                $dir = dirname($cacheFile);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                $content = '<?php return ' . var_export(self::$resolved, true) . ';';
                file_put_contents($cacheFile, $content);
            }
        });
    }

    /**
     * Intenta resolver una clase que no existe en su ubicación original.
     *
     * Casos que maneja:
     * 1. FacturaScripts\Dinamic\{tipo}\{Clase} → busca en Plugins, luego Core
     * 2. FacturaScripts\Core\{tipo}\{Clase}    → busca en Plugins (por si se movió)
     */
    public static function resolve(string $class): void
    {
        // Solo nos interesan clases de FacturaScripts
        if (!str_starts_with($class, 'FacturaScripts\\')) {
            return;
        }

        // Si ya la resolvimos, crear el alias directamente
        if (isset(self::$resolved[$class])) {
            class_alias(self::$resolved[$class], $class);
            return;
        }

        $realClass = self::getRealClass($class);
        if ($realClass !== null && $realClass !== $class) {
            self::$resolved[$class] = $realClass;
            self::$dirty = true;
            class_alias($realClass, $class);
        }
    }

    /**
     * Busca la clase real recorriendo los plugins activos y el Core.
     * Puede ser usada por el sistema moderno para resolver strings de plugins legacy.
     */
    public static function getRealClass(string $class): ?string
    {
        // Caso 1: FacturaScripts\Dinamic\Controller\EditProducto
        if (str_starts_with($class, 'FacturaScripts\\Dinamic\\')) {
            $suffix = substr($class, strlen('FacturaScripts\\Dinamic\\'));
            return self::searchInPluginsAndCore($suffix);
        }

        // Caso 2: FacturaScripts\Core\Controller\EditProducto (movida a un Plugin)
        if (str_starts_with($class, 'FacturaScripts\\Core\\')) {
            $suffix = substr($class, strlen('FacturaScripts\\Core\\'));
            // Solo buscamos en plugins, porque si existiera en Core ya la habría cargado Composer
            return self::searchInPlugins($suffix);
        }

        // Caso 3: FacturaScripts\Plugins\Trading\Model\CodeModel (clase sin importar que resuelve al namespace del plugin)
        if (str_starts_with($class, 'FacturaScripts\\Plugins\\')) {
            $parts = explode('\\', $class);
            if (count($parts) >= 5) {
                $suffix = implode('\\', array_slice($parts, 3)); // Ej: Model\CodeModel
                $coreClass = 'FacturaScripts\\Core\\' . $suffix;
                if (class_exists($coreClass, true)) {
                    return $coreClass;
                }
            }
        }

        return null;
    }

    /**
     * Busca una clase en los plugins activos (orden inverso = última gana)
     * y luego en el Core como fallback.
     */
    private static function searchInPluginsAndCore(string $suffix): ?string
    {
        // 1. Plugins (orden inverso)
        $result = self::searchInPlugins($suffix);
        if ($result !== null) {
            return $result;
        }

        // 2. Modules (Hexagonal)
        $result = self::searchInModules($suffix);
        if ($result !== null) {
            return $result;
        }

        // 3. Fallback al Core
        $coreClass = 'FacturaScripts\\Core\\' . $suffix;
        if (class_exists($coreClass, true)) {
            return $coreClass;
        }

        return null;
    }

    /**
     * Busca una clase en los módulos hexagonales activos.
     */
    private static function searchInModules(string $suffix): ?string
    {
        // Leemos las carpetas dentro de Modules/
        $modulesDir = FS_FOLDER . '/Modules';
        if (!is_dir($modulesDir)) {
            return null;
        }

        $modules = scandir($modulesDir);
        foreach ($modules as $module) {
            if ($module === '.' || $module === '..' || !is_dir($modulesDir . '/' . $module)) {
                continue;
            }

            $moduleClass = 'Modules\\' . $module . '\\' . $suffix;
            if (class_exists($moduleClass, true)) {
                return $moduleClass;
            }
        }

        return null;
    }

    /**
     * Busca una clase en los plugins activos.
     */
    private static function searchInPlugins(string $suffix): ?string
    {
        // Intentamos cargar la clase de Plugins si está disponible,
        // pero evitamos fallar si la tabla de plugins no está lista aún.
        try {
            $plugins = class_exists(Plugins::class) ? Plugins::enabled() : [];
        } catch (\Throwable $e) {
            $plugins = [];
        }

        // Si no hay plugins activados (caso de instalación limpia),
        // escaneamos físicamente la carpeta Plugins/ para permitir que el instalador funcione.
        if (empty($plugins)) {
            $pluginsDir = FS_FOLDER . '/Plugins';
            if (is_dir($pluginsDir)) {
                $folders = scandir($pluginsDir);
                foreach ($folders as $folder) {
                    if ($folder === '.' || $folder === '..' || !is_dir($pluginsDir . '/' . $folder)) {
                        continue;
                    }
                    $plugins[] = $folder;
                }
            }
        }

        // Recorremos en orden inverso (último plugin activado/encontrado = mayor prioridad)
        foreach (array_reverse($plugins) as $plugin) {
            $pluginClass = 'FacturaScripts\\Plugins\\' . $plugin . '\\' . $suffix;
            if (class_exists($pluginClass, true)) {
                return $pluginClass;
            }
        }

        return null;
    }

    /**
     * Devuelve el mapa completo de resoluciones hechas hasta el momento.
     * Útil para debug y para la DebugBar.
     */
    public static function getResolved(): array
    {
        return self::$resolved;
    }

    /**
     * Limpia la caché (útil para tests).
     */
    public static function clear(): void
    {
        self::$resolved = [];
    }
}
