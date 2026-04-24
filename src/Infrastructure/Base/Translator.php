<?php

declare(strict_types=1);

namespace Tahiche\Infrastructure\Base;

use FacturaScripts\Core\Tools;

/**
 * Versión estrangulada del Traductor.
 * Delega en FacturaScripts\Core\Tools::trans().
 */
class Translator
{
    public static function trans(string $key, array $params = []): string
    {
        return \FacturaScripts\Core\Tools::trans($key, $params);
    }
}
