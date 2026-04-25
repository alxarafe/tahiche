<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\ComercialContactController;
use FacturaScripts\Core\Lib\ExtendedController\EditListView;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\CustomerRiskTools;
use FacturaScripts\Dinamic\Lib\InvoiceOperation;
use FacturaScripts\Dinamic\Lib\RegimenIVA;
use FacturaScripts\Core\Lib\TaxExceptions;
use FacturaScripts\Dinamic\Model\AlbaranCliente;
use FacturaScripts\Dinamic\Model\Cliente;

use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\PedidoCliente;
use FacturaScripts\Dinamic\Model\PresupuestoCliente;

/**
 * Controller to edit a single item from the Cliente model
 *
 * @author       Carlos García Gómez           <carlos@facturascripts.com>
 * @author       Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author       Fco. Antonio Moreno Pérez     <famphuelva@gmail.com>
 * @collaborator Daniel Fernández Giménez      <contacto@danielfg.es>
 */
class EditCliente extends ComercialContactController
{
    /**
     * Returns the customer's risk on pending delivery notes.
     *
     * @return string
     */
    public function getDeliveryNotesRisk(): string
    {
        $codcliente = $this->getViewModelValue('EditCliente', 'codcliente');
        $total = empty($codcliente) ? 0 : CustomerRiskTools::getDeliveryNotesRisk($codcliente);
        return Tools::money($total);
    }

    public function getImageUrl(): string
    {
        $mvn = $this->getMainViewName();
        return $this->views[$mvn]->model->gravatar();
    }

    /**
     * Returns the customer's risk on unpaid invoices.
     *
     * @return string
     */
    public function getInvoicesRisk(): string
    {
        $codcliente = $this->getViewModelValue('EditCliente', 'codcliente');
        $total = empty($codcliente) ? 0 : CustomerRiskTools::getInvoicesRisk($codcliente);
        return Tools::money($total);
    }

    public function getModelClassName(): string
    {
        return 'Cliente';
    }

    /**
     * Returns the customer's risk on pending orders.
     *
     * @return string
     */
    public function getOrdersRisk(): string
    {
        $codcliente = $this->getViewModelValue('EditCliente', 'codcliente');
        $total = empty($codcliente) ? 0 : CustomerRiskTools::getOrdersRisk($codcliente);
        return Tools::money($total);
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'customer';
        $data['icon'] = 'fa-solid fa-users';
        return $data;
    }

    protected function createDocumentView(string $viewName, string $model, string $label): void
    {
        $this->createCustomerListView($viewName, $model, $label)
            ->setSettings('btnPrint', true);

        // agrupamos las acciones en un dropdown
        $this->tab($viewName)->addButtonGroup([
            'name' => 'doc-actions',
            'icon' => 'fa-solid fa-circle-check',
            'label' => 'actions'
        ]);
        $this->addButtonApproveDocument($viewName, 'doc-actions');
        $this->addButtonGroupDocument($viewName, 'doc-actions');
    }

    protected function createInvoiceView(string $viewName): void
    {
        $this->createCustomerListView($viewName, 'FacturaCliente', 'invoices')
            ->setSettings('btnPrint', true);

        // agrupamos las acciones de facturas en un dropdown
        $this->tab($viewName)->addButtonGroup([
            'name' => 'invoice-actions',
            'icon' => 'fa-solid fa-circle-check',
            'label' => 'actions'
        ]);
        $this->addButtonPayInvoice($viewName, 'invoice-actions');
        $this->addButtonLockInvoice($viewName, 'invoice-actions');
    }

    /**
     * Crea todas las vista de EditCliente y sus paneles
     */
        parent::createViews();

        $this->addEditListView('EditCuentaBancoCliente', 'CuentaBancoCliente', 'customer-banking-accounts', 'fa-solid fa-piggy-bank');

        if ($this->user->can('EditSubcuenta')) {
            $this->createSubaccountsView();
        }

        $this->createEmailsView();
        $this->createViewDocFiles();

        if ($this->user->can('EditFacturaCliente')) {
            $this->createInvoiceView('ListFacturaCliente');
            $this->createLineView('ListLineaFacturaCliente', 'LineaFacturaCliente');
        }
        if ($this->user->can('EditAlbaranCliente')) {
            $this->createDocumentView('ListAlbaranCliente', 'AlbaranCliente', 'delivery-notes');
        }
        if ($this->user->can('EditPedidoCliente')) {
            $this->createDocumentView('ListPedidoCliente', 'PedidoCliente', 'orders');
        }
        if ($this->user->can('EditPresupuestoCliente')) {
            $this->createDocumentView('ListPresupuestoCliente', 'PresupuestoCliente', 'estimations');
        }
        if ($this->user->can('EditReciboCliente')) {
            $this->createReceiptView('ListReciboCliente', 'ReciboCliente');
        }
    }



    protected function editAction(): bool
    {
        $return = parent::editAction();
        if ($return && $this->active === $this->getMainViewName()) {
            $this->checkSubaccountLength($this->getModel()->codsubcuenta);
        }

        return $return;
    }



    protected function insertAction(): bool
    {
        if (false === parent::insertAction()) {
            return false;
        }

        // redirect to return_url if return is defined
        $return_url = $this->request->query('return');
        if (empty($return_url)) {
            return true;
        }

        $model = $this->views[$this->active]->model;
        if (strpos($return_url, '?') === false) {
            $this->redirect($return_url . '?' . $model->primaryColumn() . '=' . $model->id());
        } else {
            $this->redirect($return_url . '&' . $model->primaryColumn() . '=' . $model->id());
        }

        return true;
    }

    /**
     * Load view data procedure
     *
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mainViewName = $this->getMainViewName();
        $codcliente = $this->getViewModelValue($mainViewName, 'codcliente');
        $where = [new DataBaseWhere('codcliente', $codcliente)];

        switch ($viewName) {
            case 'EditCuentaBancoCliente':
                $view->loadData('', $where, ['codcuenta' => 'DESC']);
                break;



            case 'ListFacturaCliente':
                $view->loadData('', $where);
                $this->addButtonGenerateAccountingInvoices($viewName, $codcliente, 'invoice-actions');
                break;

            case 'ListAlbaranCliente':
            case 'ListPedidoCliente':
            case 'ListPresupuestoCliente':
            case 'ListReciboCliente':
                $view->loadData('', $where);
                break;

            case 'ListLineaFacturaCliente':
                $inSQL = 'SELECT idfactura FROM facturascli WHERE codcliente = ' . $this->dataBase->var2str($codcliente);
                $where = [new DataBaseWhere('idfactura', $inSQL, 'IN')];
                $view->loadData('', $where);
                break;

            case $mainViewName:
                parent::loadData($viewName, $view);
                $this->loadLanguageValues($viewName);
                $this->loadExceptionVat($viewName);
                $this->loadOperationValues($viewName);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    protected function loadExceptionVat(string $viewName): void
    {
        $column = $this->views[$viewName]->columnForName('vat-exception');
        if ($column && $column->widget->getType() === 'select') {
            $column->widget->setValuesFromArrayKeys(TaxExceptions::all(), true, true);
        }
    }

    protected function loadOperationValues(string $viewName): void
    {
        $column = $this->views[$viewName]->columnForName('operation');
        if ($column && $column->widget->getType() === 'select') {
            $column->widget->setValuesFromArrayKeys(InvoiceOperation::allForSales(), true, true);
        }
    }

    /**
     * Load the available language values from translator.
     */
    protected function loadLanguageValues(string $viewName): void
    {
        $columnLangCode = $this->views[$viewName]->columnForName('language');
        if ($columnLangCode && $columnLangCode->widget->getType() === 'select') {
            $langs = [];
            foreach (Tools::lang()->getAvailableLanguages() as $key => $value) {
                $langs[] = ['value' => $key, 'title' => $value];
            }

            $columnLangCode->widget->setValuesFromArray($langs, false, true);
        }
    }

    protected function setCustomWidgetValues(string $viewName): void
    {
        // Load values option to VAT Type select input
        $columnVATType = $this->views[$viewName]->columnForName('vat-regime');
        if ($columnVATType && $columnVATType->widget->getType() === 'select') {
            $columnVATType->widget->setValuesFromArrayKeys(RegimenIVA::all(), true);
        }
    }


}
