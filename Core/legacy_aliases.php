<?php

/**
 * Dinamicamente crea alias de Tahiche\Core\* a FacturaScripts\Core\*
 * para mantener la compatibilidad con el código legacy y los tests.
 */
spl_autoload_register(function ($class) {
    if (str_starts_with($class, 'Tahiche\\Core\\')) {
        $legacyClass = str_replace('Tahiche\\Core\\', 'FacturaScripts\\Core\\', $class);
        if (class_exists($legacyClass)) {
            class_alias($legacyClass, $class);
        }
    }
}, true, true);
