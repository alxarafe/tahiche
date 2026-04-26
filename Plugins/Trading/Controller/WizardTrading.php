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

namespace FacturaScripts\Plugins\Trading\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\RegimenIVA;
use FacturaScripts\Dinamic\Model\User;

/**
 * Wizard for trading configuration.
 * Handles tax regime, default tax, payment methods, invoice numbering, and bank accounts.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class WizardTrading extends Controller
{
    const ITEM_SELECT_LIMIT = 500;

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'wizard-trading';
        $data['icon'] = 'fa-solid fa-store';
        $data['showonmenu'] = false;
        return $data;
    }

    public function getRegimenIva(): array
    {
        $list = ['' => '------'];
        foreach (RegimenIVA::all() as $key => $value) {
            $list[$key] = Tools::trans($value);
        }
        return $list;
    }

    /**
     * Returns an array with all data from selected model.
     *
     * @param string $modelName
     * @param bool $addEmpty
     *
     * @return array
     */
    public function getSelectValues(string $modelName, bool $addEmpty = false): array
    {
        $values = $addEmpty ? ['' => '------'] : [];
        $modelClassName = '\\FacturaScripts\\Dinamic\\Model\\' . $modelName;
        if (false === class_exists($modelClassName)) {
            return $values;
        }

        $model = new $modelClassName();

        $order = [$model->primaryDescriptionColumn() => 'ASC'];
        foreach ($model->all([], $order, 0, self::ITEM_SELECT_LIMIT) as $newModel) {
            $values[$newModel->primaryColumnValue()] = $newModel->primaryDescription();
        }

        return $values;
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

        $this->empresa->regimeniva = $this->request->input('regimeniva');
        $this->empresa->save();

        $codimpuesto = $this->request->input('codimpuesto');
        Tools::settingsSet('default', 'codimpuesto', empty($codimpuesto) ? null : $codimpuesto);

        $codpago = $this->request->input('codpago');
        Tools::settingsSet('default', 'codpago', empty($codpago) ? null : $codpago);

        Tools::settingsSet('default', 'ventasinstock', (bool)$this->request->input('ventasinstock', '0'));
        Tools::settingsSet('default', 'site_url', Tools::siteUrl());
        Tools::settingsSave();

        $this->saveInvoiceStartNumber();
        $this->saveBankAccount();

        Tools::log()->notice('record-updated-correctly');
        $this->redirect('Dashboard', 2);
    }

    private function saveBankAccount(): void
    {
        $iban = $this->request->input('iban', '');
        $bankName = $this->request->input('bank_name', '');
        if (empty($iban) && empty($bankName)) {
            return;
        }

        $paymentMethod = $this->getTransferPaymentMethod();
        if (false === $paymentMethod->exists()) {
            return;
        }

        if (class_exists('\\FacturaScripts\\Dinamic\\Model\\CuentaBanco')) {
            $account = new \FacturaScripts\Dinamic\Model\CuentaBanco();
            if (!empty($paymentMethod->codcuentabanco)) {
                $account->load($paymentMethod->codcuentabanco);
            }

            $account->descripcion = empty($bankName) ? $this->empresa->nombrecorto : $bankName;
            $account->iban = $iban;
            $account->idempresa = $this->empresa->idempresa;
            if (false === $account->save()) {
                return;
            }

            $paymentMethod->codcuentabanco = $account->codcuenta;
            $paymentMethod->idempresa = $this->empresa->idempresa;
            $paymentMethod->save();
        }
    }

    private function saveInvoiceStartNumber(): void
    {
        $startNumber = (int)$this->request->input('invoice_start_number', '1');
        if ($startNumber < 2) {
            return;
        }

        $exerciseCode = $this->getCompanyExerciseCode();
        if (empty($exerciseCode)) {
            return;
        }

        if (!class_exists('\\FacturaScripts\\Dinamic\\Model\\SecuenciaDocumento')) {
            return;
        }

        // buscamos las secuencias de FacturaCliente para actualizar el número de inicio
        $secuencia = new \FacturaScripts\Dinamic\Model\SecuenciaDocumento();
        $where = [
            Where::eq('codejercicio', $exerciseCode),
            Where::eq('codserie', 'A'),
            Where::eq('tipodoc', 'FacturaCliente'),
            Where::eq('idempresa', $this->empresa->idempresa),
        ];
        $found = false;
        foreach ($secuencia->all($where) as $sec) {
            $found = true;
            $sec->inicio = $startNumber;
            $sec->numero = $startNumber;
            $sec->patron = 'F{EJE}{SERIE}{NUM}';
            $sec->save();
        }
        if ($found) {
            return;
        }

        // si no existe la secuencia, la creamos
        $secuencia->codejercicio = $exerciseCode;
        $secuencia->codserie = 'A';
        $secuencia->idempresa = $this->empresa->idempresa;
        $secuencia->inicio = $startNumber;
        $secuencia->numero = $startNumber;
        $secuencia->patron = 'F{EJE}{SERIE}{NUM}';
        $secuencia->tipodoc = 'FacturaCliente';
        $secuencia->usarhuecos = true;
        $secuencia->save();
    }

    private function getTransferPaymentMethod()
    {
        if (!class_exists('\\FacturaScripts\\Dinamic\\Model\\FormaPago')) {
            return new class {
                public function exists()
                {
                    return false;
                }
            };
        }

        $paymentMethod = new \FacturaScripts\Dinamic\Model\FormaPago();
        if ($paymentMethod->load('TRANS')) {
            return $paymentMethod;
        }

        $paymentMethod->codpago = 'TRANS';
        $paymentMethod->descripcion = 'Transferencia bancaria';
        $paymentMethod->idempresa = $this->empresa->idempresa;
        $paymentMethod->plazovencimiento = 1;
        $paymentMethod->tipovencimiento = 'months';
        $paymentMethod->save();
        return $paymentMethod;
    }

    private function getCompanyExerciseCode(): string
    {
        foreach ($this->empresa->getExercises() as $exercise) {
            return (string)$exercise->codejercicio;
        }

        return '';
    }
}
