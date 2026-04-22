<?php

namespace Modules\Admin\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Admin\Model\SentEmail;

class SentEmailsController extends ResourceController
{
    #[\Override]
    protected function getModelClassName(): string
    {
        return SentEmail::class;
    }
}
