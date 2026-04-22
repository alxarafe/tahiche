<?php

namespace Modules\Admin\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Admin\Model\EmailNotification;

class EmailNotificationsController extends ResourceController
{
    #[\Override]
    protected function getModelClassName(): string
    {
        return EmailNotification::class;
    }
}
