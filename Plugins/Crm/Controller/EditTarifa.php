<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 */

namespace FacturaScripts\Plugins\Crm\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\Crm\Model\Cliente;
use FacturaScripts\Plugins\Crm\Model\GrupoClientes;
use FacturaScripts\Plugins\Trading\Controller\EditTarifa as TradingEditTarifa;

/**
 * Controller extension to add CRM tabs (Cliente, GrupoClientes) to EditTarifa
 */
class EditTarifa extends TradingEditTarifa
{
    protected function createViews()
    {
        parent::createViews();
        $this->createCustomerGroupView();
        $this->createCustomerView();
    }

    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListCliente':
            case 'ListGrupoClientes':
                $codtarifa = $this->getViewModelValue($this->getMainViewName(), 'codtarifa');
                $where = [new DataBaseWhere('codtarifa', $codtarifa)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'unsetcustomerrate':
                $this->unsetCustomerRate();
                break;

            case 'unsetgrouprate':
                $this->unsetGroupRate();
                break;

            case 'setcustomerrate':
                $this->setCustomerRate();
                break;

            case 'setgrouprate':
                $this->setGroupRate();
                break;
        }

        return parent::execPreviousAction($action);
    }

    protected function createCustomerGroupView(string $viewName = 'ListGrupoClientes'): void
    {
        $this->addListView($viewName, 'GrupoClientes', 'customer-group', 'fa-solid fa-users-cog')
            ->addSearchFields(['nombre', 'codgrupo'])
            ->addOrderBy(['codgrupo'], 'code')
            ->addOrderBy(['nombre'], 'name', 1)
            ->disableColumn('rate')
            ->setSettings('btnDelete', false)
            ->setSettings('btnNew', false);

        // add custom buttons
        $this->addButton($viewName, [
            'action' => 'setgrouprate',
            'color' => 'success',
            'icon' => 'fa-solid fa-folder-plus',
            'label' => 'add',
            'type' => 'modal'
        ]);
        $this->addButton($viewName, [
            'action' => 'unsetgrouprate',
            'color' => 'danger',
            'confirm' => true,
            'icon' => 'fa-solid fa-folder-minus',
            'label' => 'remove-from-list'
        ]);
    }

    protected function createCustomerView(string $viewName = 'ListCliente'): void
    {
        $this->addListView($viewName, 'Cliente', 'customers', 'fa-solid fa-users')
            ->addSearchFields(['cifnif', 'codcliente', 'email', 'nombre', 'observaciones', 'razonsocial', 'telefono1', 'telefono2'])
            ->addOrderBy(['codcliente'], 'code')
            ->addOrderBy(['nombre'], 'name', 1)
            ->addOrderBy(['fechaalta', 'codcliente'], 'date')
            ->setSettings('btnDelete', false)
            ->setSettings('btnNew', false);

        $this->addButton($viewName, [
            'action' => 'setcustomerrate',
            'color' => 'success',
            'icon' => 'fa-solid fa-folder-plus',
            'label' => 'add',
            'type' => 'modal'
        ]);
        $this->addButton($viewName, [
            'action' => 'unsetcustomerrate',
            'color' => 'danger',
            'confirm' => true,
            'icon' => 'fa-solid fa-folder-minus',
            'label' => 'remove-from-list'
        ]);
    }

    protected function unsetCustomerRate(): void
    {
        $codes = $this->request->request->getArray('codes');
        if (empty($codes) || false === is_array($codes)) {
            Tools::log()->warning('no-selected-item');
            return;
        }

        $customer = new Cliente();
        foreach ($codes as $cod) {
            if ($customer->load($cod)) {
                $customer->codtarifa = null;
                $customer->save();
            }
        }

        Tools::log()->notice('record-updated-correctly');
    }

    protected function unsetGroupRate(): void
    {
        $codes = $this->request->request->getArray('codes');
        if (empty($codes) || false === is_array($codes)) {
            Tools::log()->warning('no-selected-item');
            return;
        }

        $group = new GrupoClientes();
        foreach ($codes as $cod) {
            if ($group->load($cod)) {
                $group->codtarifa = null;
                $group->save();
            }
        }

        Tools::log()->notice('record-updated-correctly');
    }

    protected function setCustomerRate(): void
    {
        $customer = new Cliente();
        $code = $this->request->input('setcustomerrate');
        if (empty($code) || false === $customer->load($code)) {
            Tools::log()->warning('customer-not-found');
            return;
        }

        $customer->codtarifa = $this->request->queryOrInput('code');
        if ($customer->save()) {
            Tools::log()->notice('record-updated-correctly');
            return;
        }

        Tools::log()->warning('record-save-error');
    }

    protected function setGroupRate(): void
    {
        $group = new GrupoClientes();
        $code = $this->request->input('setgrouprate');
        if (empty($code) || false === $group->load($code)) {
            Tools::log()->warning('group-not-found');
            return;
        }

        $group->codtarifa = $this->request->queryOrInput('code');
        if ($group->save()) {
            Tools::log()->notice('record-updated-correctly');
            return;
        }

        Tools::log()->warning('record-save-error');
    }
}
