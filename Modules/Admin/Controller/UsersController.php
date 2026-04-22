<?php

namespace Modules\Admin\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Admin\Model\User;

class UsersController extends ResourceController
{
    #[\Override]
    protected function getModelClassName(): string
    {
        return User::class;
    }
}
