<?php

namespace Modules\Crm\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Crm\Model\CustomerGroup;

class CustomerGroupsController extends ResourceController
{
    protected function getModelClassName(): string
    {
        return CustomerGroup::class;
    }
}
