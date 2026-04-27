<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\DataSrc;

use FacturaScripts\Core\Base\AbstractDataSrc;
use FacturaScripts\Core\Model\Pais;
use FacturaScripts\Core\Tools;

final class Paises extends AbstractDataSrc
{
    const MIEMBROS_UE = [
        'DE', 'AT', 'BE', 'BG', 'CZ', 'CY', 'HR', 'DK', 'SK', 'SI', 'EE', 'FI', 'FR', 'GR', 'HU', 'IE', 'IT', 'LV',
        'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'GB', 'ES'
    ];

    public static function default()
    {
        $code = Tools::settings('default', 'codpais', 'ESP');
        return self::get($code);
    }

    public static function miembroUE($codpais): bool
    {
        $iso = self::get($codpais)->codiso;
        return self::miembroUEbyIso($iso);
    }

    public static function miembroUEbyIso($iso): bool
    {
        return in_array($iso, self::MIEMBROS_UE);
    }

    protected static function getCacheKey(): string
    {
        return 'model-Pais-list';
    }

    protected static function getCodeField(): string
    {
        return 'codpais';
    }

    protected static function getDescriptionField(): string
    {
        return 'nombre';
    }

    protected static function getModelClass(): string
    {
        return Pais::class;
    }

    protected static function getOrderBy(): array
    {
        return ['nombre' => 'ASC'];
    }
}
