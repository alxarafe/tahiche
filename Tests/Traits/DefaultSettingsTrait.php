<?php
/**
 * This file is part of Tahiche
 * Copyright (C) 2021-2026 Tahiche Team <tahiche@alxarafe.com>
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

namespace Tahiche\Test\Traits;

use Tahiche\Core\DataSrc\Ejercicios;
use Tahiche\Core\DataSrc\Paises;
use Tahiche\Core\Lib\Accounting\AccountingPlanImport;
use FacturaScripts\Dinamic\Model\Almacen;
use FacturaScripts\Dinamic\Model\Cuenta;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\RegularizacionImpuesto;
use Tahiche\Core\Template\ModelClass;
use Tahiche\Core\Tools;
use Tahiche\Core\Where;

trait DefaultSettingsTrait
{
    protected static function installAccountingPlan(): void
    {
        // ¿Existe el archivo del plan contable?
        $filePath = FS_FOLDER . '/Core/Data/Codpais/' . Paises::default()->codpais . '/defaultPlan.csv';
        if (false === file_exists($filePath)) {
            return;
        }

        // recorremos todos los ejercicios
        Ejercicios::clear();
        foreach (Ejercicios::all() as $exercise) {
            // si está cerrado, lo abrimos
            if (false === $exercise->isOpened()) {
                $exercise->estado = Ejercicio::EXERCISE_STATUS_OPEN;
                $exercise->save();
            }

            $where = [Where::eq('codejercicio', $exercise->codejercicio)];
            if (Cuenta::count($where) > 0) {
                // ya tiene plan contable
                continue;
            }

            // importamos el plan contable en aquellos que no tengan
            $planImport = new AccountingPlanImport();
            $planImport->importCSV($filePath, $exercise->codejercicio);
        }
    }

    protected static function loadCoreModels(): void
    {
        foreach (Tools::folderScan(Tools::folder('Core', 'Model')) as $fileName) {
            if ('.php' !== substr($fileName, -4)) {
                continue;
            }

            $className = '\\Tahiche\\Dinamic\\Model\\' . substr($fileName, 0, -4);
            if (false === is_subclass_of($className, ModelClass::class)) {
                continue;
            }

            new $className();
        }
    }

    protected static function removeTaxRegularization(): void
    {
        foreach (RegularizacionImpuesto::all() as $reg) {
            $reg->delete();
        }
    }

    protected static function setDefaultSettings(): void
    {
        $fileContent = file_get_contents(FS_FOLDER . '/Core/Data/Codpais/' . Paises::default()->codpais . '/default.json');
        $defaultValues = json_decode($fileContent, true) ?? [];
        foreach ($defaultValues as $group => $values) {
            foreach ($values as $key => $value) {
                Tools::settingsSet($group, $key, $value);
            }
        }

        $where = [Where::eq('idempresa', Tools::settings('default', 'idempresa', 1))];
        foreach (Almacen::all($where) as $warehouse) {
            Tools::settingsSet('default', 'codalmacen', $warehouse->codalmacen);
        }

        Tools::settingsSave();
    }
}
