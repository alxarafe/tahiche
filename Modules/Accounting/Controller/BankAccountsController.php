<?php

namespace Modules\Accounting\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Accounting\Model\BankAccount;

class BankAccountsController extends ResourceController
{
    protected function getModelClassName(): string
    {
        return BankAccount::class;
    }
}
