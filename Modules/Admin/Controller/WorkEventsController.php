<?php

namespace Modules\Admin\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Admin\Model\WorkEvent;

class WorkEventsController extends ResourceController
{
    #[\Override]
    protected function getModelClassName(): string
    {
        return WorkEvent::class;
    }
}
