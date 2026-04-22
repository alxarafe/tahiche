<?php

namespace Modules\Accounting\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Accounting\Model\EntryConcept;

class EntryConceptsController extends ResourceController
{
    protected function getModelClassName(): string
    {
        return EntryConcept::class;
    }
}
