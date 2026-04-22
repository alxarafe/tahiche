<?php

namespace Modules\Trading\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Trading\Model\DocumentSequence;

class DocumentSequencesController extends ResourceController
{
    protected function getModelClassName(): string
    {
        return DocumentSequence::class;
    }
}
