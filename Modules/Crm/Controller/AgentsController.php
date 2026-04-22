<?php

namespace Modules\Crm\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Crm\Model\Agent;

class AgentsController extends ResourceController
{
    protected function getModelClassName(): string
    {
        return Agent::class;
    }
}
