<?php

namespace Modules\Trading\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Trading\Model\Attribute;

class AttributesController extends ResourceController
{
    protected function getModelClassName(): string
    {
        return Attribute::class;
    }
}
