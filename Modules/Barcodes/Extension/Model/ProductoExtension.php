<?php

declare(strict_types=1);

namespace Modules\Barcodes\Extension\Model;

use Closure;
use Modules\Barcodes\Model\ProductBarcode;

class ProductoExtension
{
    /**
     * Intercepts loadFromCode when standard search fails.
     */
    public function loadFromCodeBefore(): Closure
    {
        return function ($code, array $where = [], array $order = []): ?bool {
            if (empty($where)) {
                $result = ProductBarcode::findByBarcode((string)$code);
                if ($result) {
                    /** @phpstan-ignore-next-line */
                    $this->loadFromData($result['producto']->toArray());
                    return true; // We found the product, return true to short-circuit
                }
            }

            return null; // Return null to continue execution of the original method
        };
    }
}
