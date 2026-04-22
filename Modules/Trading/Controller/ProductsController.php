<?php

namespace Modules\Trading\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Trading\Model\Product;

class ProductsController extends ResourceController
{
    #[\Override]
    protected function getModelClassName(): string
    {
        return Product::class;
    }
}
