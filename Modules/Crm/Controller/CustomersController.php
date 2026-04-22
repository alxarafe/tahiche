<?php

namespace Modules\Crm\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Crm\Model\Customer;

class CustomersController extends ResourceController
{
    #[\Override]
    protected function getModelClassName(): string
    {
        return Customer::class;
    }
}
