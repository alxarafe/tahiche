<?php

namespace Modules\Trading\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Trading\Model\PostalCode;

class PostalCodesController extends ResourceController
{
    #[\Override]
    protected function getModelClassName(): string
    {
        return PostalCode::class;
    }
}
