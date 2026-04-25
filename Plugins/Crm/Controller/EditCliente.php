<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 */

namespace FacturaScripts\Plugins\Crm\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\EditListView;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\Crm\Model\Contacto;
use FacturaScripts\Plugins\Trading\Model\AlbaranCliente;
use FacturaScripts\Plugins\Trading\Model\Cliente;
use FacturaScripts\Plugins\Trading\Model\FacturaCliente;
use FacturaScripts\Plugins\Trading\Model\PedidoCliente;
use FacturaScripts\Plugins\Trading\Model\PresupuestoCliente;
use FacturaScripts\Plugins\Trading\Controller\EditCliente as TradingEditCliente;

/**
 * Controller extension to add CRM contacts view to EditCliente
 */
class EditCliente extends TradingEditCliente
{
    protected function createViews(): void
    {
        parent::createViews();
        $this->createContactsView();
    }

    protected function createContactsView(string $viewName = 'EditDireccionContacto'): EditListView
    {
        return parent::createContactsView($viewName)
            ->addButton([
                'action' => 'update-docs-address',
                'color' => 'warning',
                'confirm' => true,
                'icon' => 'fa-solid fa-pencil',
                'label' => 'update-docs-address'
            ]);
    }

    protected function execPreviousAction($action)
    {
        if ($action == 'update-docs-address') {
            return $this->updateDocsAddressAction();
        }

        return parent::execPreviousAction($action);
    }

    protected function loadData($viewName, $view)
    {
        if ($viewName === 'EditDireccionContacto') {
            $codcliente = $this->getViewModelValue($this->getMainViewName(), 'codcliente');
            $where = [new DataBaseWhere('codcliente', $codcliente)];
            $view->loadData('', $where, ['idcontacto' => 'DESC']);
        } else {
            parent::loadData($viewName, $view);
        }
    }

    protected function setCustomWidgetValues(string $viewName): void
    {
        parent::setCustomWidgetValues($viewName);

        // Model exists?
        if (false === $this->views[$viewName]->model->exists()) {
            $this->views[$viewName]->disableColumn('billing-address');
            $this->views[$viewName]->disableColumn('shipping-address');
            return;
        }

        // Search for client contacts
        $codcliente = $this->getViewModelValue($viewName, 'codcliente');
        $where = [new DataBaseWhere('codcliente', $codcliente)];
        $contacts = $this->codeModel->all('contactos', 'idcontacto', 'descripcion', false, $where);

        // Load values option to default billing address from client contacts list
        $columnBilling = $this->views[$viewName]->columnForName('billing-address');
        if ($columnBilling && $columnBilling->widget->getType() === 'select') {
            $columnBilling->widget->setValuesFromCodeModel($contacts);
        }

        // Load values option to default shipping address from client contacts list
        $columnShipping = $this->views[$viewName]->columnForName('shipping-address');
        if ($columnShipping && $columnShipping->widget->getType() === 'select') {
            $contacts2 = $this->codeModel->all('contactos', 'idcontacto', 'descripcion', true, $where);
            $columnShipping->widget->setValuesFromCodeModel($contacts2);
        }
    }

    protected function updateDocsAddressAction(): bool
    {
        if (false === $this->validateFormToken()) {
            return false;
        } elseif (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return false;
        }

        // recoger contacto
        $contacto = new Contacto();
        $idcontacto = $this->request->input('idcontacto');
        if (false === $contacto->load($idcontacto)) {
            Tools::log()->error('address-not-found');
            return false;
        }

        // recoger cliente
        $cliente = new Cliente();
        $codcliente = $this->request->input('codcliente');
        if (false === $cliente->load($codcliente)) {
            Tools::log()->error('customer-not-found');
            return false;
        }

        $failCounter = 0;
        $successCounter = 0;

        $where = [
            new DataBaseWhere('codcliente', $codcliente),
            new DataBaseWhere('idcontactofact', $idcontacto),
            new DataBaseWhere('editable', true)
        ];

        foreach (['AlbaranCliente', 'FacturaCliente', 'PedidoCliente', 'PresupuestoCliente'] as $modelName) {
            $salesDocuments = [];
            switch ($modelName) {
                case 'AlbaranCliente':
                    $salesDocuments = AlbaranCliente::all($where);
                    break;
                case 'FacturaCliente':
                    $salesDocuments = FacturaCliente::all($where);
                    break;
                case 'PedidoCliente':
                    $salesDocuments = PedidoCliente::all($where);
                    break;
                case 'PresupuestoCliente':
                    $salesDocuments = PresupuestoCliente::all($where);
                    break;
            }

            foreach ($salesDocuments as $salesDoc) {
                $salesDoc->direccion = $contacto->direccion;
                $salesDoc->apartado = $contacto->apartado;
                $salesDoc->codpostal = $contacto->codpostal;
                $salesDoc->ciudad = $contacto->ciudad;
                $salesDoc->provincia = $contacto->provincia;
                $salesDoc->codpais = $contacto->codpais;

                if (false === $salesDoc->save()) {
                    $failCounter += 1;
                } else {
                    $successCounter += 1;
                }
            }
        }

        if ($failCounter === 0) {
            Tools::log()->notice('address-applied-to-documents-successfully', [
                '%successes%' => $successCounter
            ]);
        } else {
            Tools::log()->warning('address-applied-to-documents-with-errors', [
                '%failures%' => $failCounter,
                '%successes%' => $successCounter
            ]);
        }

        return true;
    }
}
