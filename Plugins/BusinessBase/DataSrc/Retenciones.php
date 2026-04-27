<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Plugins\BusinessBase\DataSrc;

use FacturaScripts\Core\Base\AbstractDataSrc;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Retencion;

final class Retenciones extends AbstractDataSrc
{
    public static function default()
    {
        $code = Tools::settings('default', 'codretencion', '');
        return self::get($code);
    }

    protected static function getCacheKey(): string
    {
        return 'model-Retencion-list';
    }

    protected static function getCodeField(): string
    {
        return 'codretencion';
    }

    protected static function getDescriptionField(): string
    {
        return 'descripcion';
    }

    protected static function getModelClass(): string
    {
        return Retencion::class;
    }

    protected static function getOrderBy(): array
    {
        return ['codretencion' => 'ASC'];
    }
}
