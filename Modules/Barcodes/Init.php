<?php

declare(strict_types=1);

namespace Modules\Barcodes;

use FacturaScripts\Core\Model\Producto;
use Modules\Barcodes\Extension\Model\ProductoExtension;

class Init
{
    public static function run(): void
    {
        // Register barcode extensions
        Producto::addExtension(new ProductoExtension());
    }
}
