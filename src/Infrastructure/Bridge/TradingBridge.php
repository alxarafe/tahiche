<?php

declare(strict_types=1);

namespace Tahiche\Infrastructure\Bridge;

use FacturaScripts\Plugins\Trading\Model\Producto;

/**
 * Bridge para interactuar con el módulo legacy Trading.
 */
class TradingBridge
{
    /**
     * Registra una extensión para el modelo Producto de Trading.
     */
    public static function addProductoExtension($extension): void
    {
        Producto::addExtension($extension);
    }
}
