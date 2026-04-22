<?php

namespace Modules\Accounting\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Accounting\Model\SpecialAccount;

class SpecialAccountsController extends ResourceController
{
    #[\Override]
    protected function getModelClassName(): string
    {
        return SpecialAccount::class;
    }
}
