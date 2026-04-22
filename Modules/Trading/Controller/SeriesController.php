<?php

namespace Modules\Trading\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Trading\Model\Series;

class SeriesController extends ResourceController
{
    protected function getModelClassName(): string
    {
        return Series::class;
    }
}
