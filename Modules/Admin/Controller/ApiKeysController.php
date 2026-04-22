<?php

namespace Modules\Admin\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Admin\Model\ApiKey;

class ApiKeysController extends ResourceController
{
    #[\Override]
    protected function getModelClassName(): string
    {
        return ApiKey::class;
    }
}
