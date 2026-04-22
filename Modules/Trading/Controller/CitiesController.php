<?php

namespace Modules\Trading\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Trading\Model\City;

class CitiesController extends ResourceController
{
    #[\Override]
    protected function getModelClassName(): string
    {
        return City::class;
    }
}
