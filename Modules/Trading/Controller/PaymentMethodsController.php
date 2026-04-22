<?php

namespace Modules\Trading\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Trading\Model\PaymentMethod;

class PaymentMethodsController extends ResourceController
{
    protected function getModelClassName(): string
    {
        return PaymentMethod::class;
    }
}
