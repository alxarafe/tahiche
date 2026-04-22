<?php

namespace Modules\Accounting\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Accounting\Model\Account;

class AccountsController extends ResourceController
{
    protected function getModelClassName(): string
    {
        return Account::class;
    }
}
