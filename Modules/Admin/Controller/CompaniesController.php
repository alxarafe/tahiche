<?php

namespace Modules\Admin\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Admin\Model\Company;

class CompaniesController extends ResourceController
{
    #[\Override]
    protected function getModelClassName(): string
    {
        return Company::class;
    }
}
