<?php

namespace Modules\Admin\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Admin\Model\Role;

class RolesController extends ResourceController
{
    #[\Override]
    protected function getModelClassName(): string
    {
        return Role::class;
    }
}
