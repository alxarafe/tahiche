<?php

namespace Modules\Trading\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Trading\Model\CityPoint;

class CityPointsController extends ResourceController
{
    protected function getModelClassName(): string
    {
        return CityPoint::class;
    }
}
