<?php

namespace Modules\Admin\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Admin\Model\ApiKey;

class ApiKeysController extends ResourceController
{
    protected function getModelClassName(): string
    {
        return ApiKey::class;
    }
}
