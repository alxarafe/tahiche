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

namespace FacturaScripts\Core\Base;

use FacturaScripts\Core\Cache;
use FacturaScripts\Dinamic\Model\CodeModel;

abstract class AbstractDataSrc
{
    /** @var array */
    protected static $lists = [];

    public static function all(): array
    {
        $class = static::class;
        if (!isset(self::$lists[$class])) {
            self::$lists[$class] = Cache::remember(static::getCacheKey(), function () {
                $modelClass = static::getModelClass();
                if (class_exists($modelClass)) {
                    return $modelClass::all([], static::getOrderBy(), 0, 0);
                }
                return [];
            });
        }

        return self::$lists[$class];
    }

    public static function clear(): void
    {
        unset(self::$lists[static::class]);
    }

    public static function codeModel(bool $addEmpty = true): array
    {
        $codes = [];
        $codeField = static::getCodeField();
        $descriptionField = static::getDescriptionField();
        foreach (static::all() as $item) {
            $codes[$item->{$codeField}] = $item->{$descriptionField};
        }

        return CodeModel::array2codeModel($codes, $addEmpty);
    }

    public static function get($code)
    {
        foreach (static::all() as $item) {
            if ($item->id() === $code) {
                return $item;
            }
        }

        $modelClass = static::getModelClass();
        if (class_exists($modelClass)) {
            return $modelClass::find($code) ?? new $modelClass();
        }

        return null;
    }

    abstract protected static function getCacheKey(): string;

    abstract protected static function getCodeField(): string;

    abstract protected static function getDescriptionField(): string;

    abstract protected static function getModelClass(): string;

    protected static function getOrderBy(): array
    {
        return [];
    }
}
