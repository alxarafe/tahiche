<?php

namespace Modules\Admin\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Admin\Model\CronJob;

class CronJobsController extends ResourceController
{
    protected function getModelClassName(): string
    {
        return CronJob::class;
    }
}
