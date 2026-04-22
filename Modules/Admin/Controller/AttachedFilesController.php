<?php

namespace Modules\Admin\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Admin\Model\AttachedFile;

class AttachedFilesController extends ResourceController
{
    #[\Override]
    protected function getModelClassName(): string
    {
        return AttachedFile::class;
    }
}
