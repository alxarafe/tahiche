<?php

namespace Modules\Trading\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Trading\Model\Province;

class ProvincesController extends ResourceController
{
    protected function getModelClassName(): string
    {
        return Province::class;
    }
}
