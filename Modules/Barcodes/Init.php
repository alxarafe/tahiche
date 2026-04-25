<?php

declare(strict_types=1);

namespace Modules\Barcodes;

use FacturaScripts\Plugins\Trading\Model\Producto;
use Modules\Barcodes\Extension\Model\ProductExtension;

class Init
{
    public static function run(): void
    {
        // Register barcode extensions
        Producto::addExtension(new ProductExtension());
    }
}
