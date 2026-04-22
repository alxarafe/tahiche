<?php

namespace Modules\Crm\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Crm\Model\Contact;

class ContactsController extends ResourceController
{
    #[\Override]
    protected function getModelClassName(): string
    {
        return Contact::class;
    }
}
