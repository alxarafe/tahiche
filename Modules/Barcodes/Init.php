<?php

declare(strict_types=1);

namespace Modules\Barcodes;

use Tahiche\Infrastructure\Bridge\TradingBridge;
use Modules\Barcodes\Extension\Model\ProductExtension;

class Init
{
    public static function run(): void
    {
        // Register barcode extensions
        TradingBridge::addProductoExtension(new ProductExtension());
    }
}
