<?php

namespace Modules\Trading\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Trading\Model\Supplier;

class SuppliersController extends ResourceController
{
    protected function getModelClassName(): string
    {
        return Supplier::class;
    }
}
