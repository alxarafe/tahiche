<?php

namespace Modules\Accounting\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Accounting\Model\Journal;

class JournalsController extends ResourceController
{
    #[\Override]
    protected function getModelClassName(): string
    {
        return Journal::class;
    }
}
