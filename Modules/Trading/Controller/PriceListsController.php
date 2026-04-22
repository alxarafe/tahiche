<?php

namespace Modules\Trading\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Trading\Model\PriceList;

class PriceListsController extends ResourceController
{
    #[\Override]
    protected function getModelClassName(): string
    {
        return PriceList::class;
    }
}
