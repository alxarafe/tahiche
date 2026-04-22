<?php

namespace Modules\Admin\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Admin\Model\LogMessage;

class LogMessagesController extends ResourceController
{
    protected function getModelClassName(): string
    {
        return LogMessage::class;
    }
}
