<?php

/*
 * Copyright (C) 2024-2026 Rafael San José <rsanjose@alxarafe.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Modules\Trading\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Trading\Model\Tax;

class TaxesController extends ResourceController
{
    protected function getModelClassName(): string
    {
        return Tax::class;
    }

    public function getPageData(): array
    {
        return [
            'name'       => 'ListImpuesto',
            'title'      => 'taxes',
            'icon'       => 'fa-solid fa-percent',
            'menu'       => 'accounting',
            'submenu'    => null,
            'showonmenu' => true,
            'ordernum'   => 100,
        ];
    }
}
