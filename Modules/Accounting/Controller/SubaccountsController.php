<?php

namespace Modules\Accounting\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Accounting\Model\Subaccount;

class SubaccountsController extends ResourceController
{
    #[\Override]
    protected function getModelClassName(): string
    {
        return Subaccount::class;
    }
}
