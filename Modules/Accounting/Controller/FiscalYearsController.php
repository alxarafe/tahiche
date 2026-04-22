<?php

namespace Modules\Accounting\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Accounting\Model\FiscalYear;

class FiscalYearsController extends ResourceController
{
    #[\Override]
    protected function getModelClassName(): string
    {
        return FiscalYear::class;
    }
}
