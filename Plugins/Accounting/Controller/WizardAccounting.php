<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Plugins\Accounting\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Accounting\AccountingPlanImport;
use FacturaScripts\Dinamic\Model\Cuenta;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\User;

/**
 * Wizard for accounting configuration.
 * Handles the default accounting plan import.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class WizardAccounting extends Controller
{
    /** @var bool */
    public $hasPlan = false;

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'wizard-accounting';
        $data['icon'] = 'fa-solid fa-book';
        $data['showonmenu'] = false;
        return $data;
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response $response
     * @param User $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        // check if there is an accounting plan available for this country
        $codpais = Tools::settings('default', 'codpais', 'ESP');
        $filePath = FS_FOLDER . '/var/cache/assets/Data/Codpais/' . $codpais . '/defaultPlan.csv';
        $this->hasPlan = file_exists($filePath);

        $action = $this->request->inputOrQuery('action', '');
        if ($action === 'save') {
            $this->saveConfig();
        }
    }

    private function saveConfig(): void
    {
        if (false === $this->validateFormToken()) {
            return;
        }

        if ($this->request->input('defaultplan', '0')) {
            $this->loadDefaultAccountingPlan();
        }

        Tools::log()->notice('record-updated-correctly');
        $this->redirect('Dashboard', 2);
    }

    /**
     * Loads the default accounting plan for the configured country.
     */
    private function loadDefaultAccountingPlan(): void
    {
        $codpais = Tools::settings('default', 'codpais', 'ESP');

        // ¿Hay un plan contable para ese país?
        $filePath = FS_FOLDER . '/var/cache/assets/Data/Codpais/' . $codpais . '/defaultPlan.csv';
        if (false === file_exists($filePath)) {
            return;
        }

        // ¿La base de datos es de 2017 o anterior?
        if ($this->dataBase->tableExists('co_cuentas')) {
            return;
        }

        // ¿Ya existe el plan contable?
        $cuenta = new Cuenta();
        if ($cuenta->count() > 0) {
            return;
        }

        foreach (Ejercicio::all() as $exercise) {
            $planImport = new AccountingPlanImport();
            $planImport->importCSV($filePath, $exercise->codejercicio);
            return;
        }
    }
}
