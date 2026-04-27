<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\DataSrc\Ejercicios;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\BusinessDocumentGenerator;

/**
 * Controller for editing models that are related and show
 * a history of purchase or sale documents.
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
abstract class ComercialContactController extends EditController
{
    use ListBusinessActionTrait;
    use DocFilesTrait;

    private $logLevels = ['critical', 'error', 'info', 'notice', 'warning'];

    protected function addCommonViewFilters(string $viewName, string $modelName): void
    {
        BusinessFilters::addDocumentFilters(
            $this->listView($viewName),
            $this->codeModel,
            $modelName,
            $this->permissions->onlyOwnerData
        );
    }

    /**
     * Set custom configuration when load main data
     *
     * @param string $viewName
     */
    abstract protected function setCustomWidgetValues(string $viewName): void;

    /**
     * Check that the subaccount length is correct.
     *
     * @param ?string $code
     */
    protected function checkSubaccountLength(?string $code): void
    {
        if (empty($code)) {
            return;
        }

        foreach (class_exists(Ejercicios::class) ? Ejercicios::all() : [] as $exe) {
            if ($exe->isOpened() && strlen($code) != $exe->longsubcuenta) {
                Tools::log()->warning('account-length-error', ['%code%' => $code]);
            }
        }
    }

    protected function checkViesAction(): bool
    {
        $model = $this->getModel();
        $code = $this->request->input('code');
        if (false === $model->loadFromCode($code)) {
            return true;
        }

        if ($model->checkVies()) {
            Tools::log()->notice('vies-check-success', ['%vat-number%' => $model->cifnif]);
        }

        return true;
    }

    /**
     * Add a Contact List View.
     *
     * @param string $viewName
     */
    protected function createContactsView(string $viewName = 'EditDireccionContacto'): EditListView
    {
        return $this->addEditListView($viewName, 'Contacto', 'addresses-and-contacts', 'fa-solid fa-address-book');
    }

    /**
     * Add a Customer document List View.
     *
     * @param string $viewName
     * @param string $model
     * @param string $label
     */
    protected function createCustomerListView(string $viewName, string $model, string $label): ListView
    {
        return $this->createListView($viewName, $model, $label, $this->getCustomerFields());
    }

    /**
     * Add an Email Sent List View.
     *
     * @param string $viewName
     */
    protected function createEmailsView(string $viewName = 'ListEmailSent'): ListView
    {
        return $this->addListView($viewName, 'EmailSent', 'emails-sent', 'fa-solid fa-envelope')
            ->addOrderBy(['date'], 'date', 2)
            ->addSearchFields(['addressee', 'body', 'subject'])
            ->disableColumn('to')
            ->setSettings('btnNew', false)
            ->addFilterPeriod('period', 'date', 'date', true);
    }

    /**
     * Add Product Lines from documents.
     *
     * @param string $viewName
     * @param string $model
     * @param string $label
     */
    protected function createLineView(string $viewName, string $model, string $label = 'products'): ListView
    {
        return $this->addListView($viewName, $model, $label, 'fa-solid fa-cubes')
            ->addOrderBy(['idlinea'], 'code', 2)
            ->addOrderBy(['cantidad'], 'quantity')
            ->addOrderBy(['pvptotal'], 'amount')
            ->addSearchFields(['referencia', 'descripcion'])
            ->setSettings('btnDelete', false)
            ->setSettings('btnNew', false)
            ->setSettings('btnPrint', true);
    }

    /**
     * Add a document List View
     *
     * @param string $viewName
     * @param string $model
     * @param string $label
     * @param array $fields
     */
    private function createListView(string $viewName, string $model, string $label, array $fields): ListView
    {
        $view = $this->addListView($viewName, $model, $label, 'fa-regular fa-file')
            ->addOrderBy(['codigo'], 'code')
            ->addOrderBy(['fecha', 'hora'], 'date', 2)
            ->addOrderBy(['numero'], 'number')
            ->addOrderBy([$fields['numfield']], $fields['numtitle'])
            ->addOrderBy(['total'], 'amount')
            ->addSearchFields(['codigo', 'observaciones', $fields['numfield']])
            ->disableColumn($fields['linkfield'], true);

        $this->addCommonViewFilters($viewName, $model);

        return $view;
    }

    /**
     * Add a receipt list view.
     *
     * @param string $viewName
     * @param string $model
     */
    protected function createReceiptView(string $viewName, string $model): ListView
    {
        return $this->addListView($viewName, $model, 'receipts', 'fa-solid fa-dollar-sign')
            ->addOrderBy(['fecha'], 'date')
            ->addOrderBy(['fechapago'], 'payment-date')
            ->addOrderBy(['vencimiento'], 'expiration', 2)
            ->addOrderBy(['importe'], 'amount')
            ->addSearchFields(['codigofactura', 'observaciones'])
            ->addFilterPeriod('period-f', 'fecha', 'fecha')
            ->addFilterPeriod('period-v', 'expiration', 'vencimiento')
            ->addButton([
                'action' => 'pay-receipt',
                'color' => 'outline-success',
                'confirm' => 'true',
                'icon' => 'fa-solid fa-check',
                'label' => 'paid',
                'type' => 'action'
            ])
            ->setSettings('btnPrint', true)
            ->setSettings('btnNew', false)
            ->setSettings('btnDelete', false)
            ->disableColumn('customer')
            ->disableColumn('supplier');
    }

    /**
     * Add Subaccount List View.
     *
     * @param string $viewName
     */
    protected function createSubaccountsView(string $viewName = 'ListSubcuenta'): ListView
    {
        return $this->addListView($viewName, 'Subcuenta', 'subaccounts', 'fa-solid fa-book')
            ->addOrderBy(['codsubcuenta'], 'code')
            ->addOrderBy(['codejercicio'], 'exercise', 2)
            ->addOrderBy(['descripcion'], 'description')
            ->addOrderBy(['saldo'], 'balance')
            ->addSearchFields(['codsubcuenta', 'descripcion'])
            ->setSettings('btnDelete', false)
            ->setSettings('btnNew', false)
            ->setSettings('checkBoxes', false);
    }

    /**
     * Add a Supplier document List View
     *
     * @param string $viewName
     * @param string $model
     * @param string $label
     */
    protected function createSupplierListView(string $viewName, string $model, string $label): ListView
    {
        return $this->createListView($viewName, $model, $label, $this->getSupplierFields());
    }

    /**
     * Run the actions that alter data before reading it.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        $allowUpdate = $this->permissions->allowUpdate;
        $codes = $this->request->request->getArray('codes');
        $model = $this->views[$this->active]->model;

        switch ($action) {
            case 'add-file':
                return $this->addFileAction();

            case 'approve-document':
                return $this->approveDocumentAction($codes, $model, $allowUpdate, $this->dataBase);

            case 'approve-document-same-date':
                BusinessDocumentGenerator::setSameDate(true);
                return $this->approveDocumentAction($codes, $model, $allowUpdate, $this->dataBase);

            case 'check-vies':
                return $this->checkViesAction();

            case 'delete-file':
                return $this->deleteFileAction();

            case 'edit-file':
                return $this->editFileAction();

            case 'generate-accounting-entries':
                return $this->generateAccountingEntriesAction($model, $allowUpdate, $this->dataBase);

            case 'group-document':
                return $this->groupDocumentAction($codes, $model);

            case 'lock-invoice':
                return $this->lockInvoiceAction($codes, $model, $allowUpdate, $this->dataBase);

            case 'pay-invoice':
                return $this->payInvoiceAction($codes, $model, $allowUpdate, $this->dataBase, $this->user->nick);

            case 'pay-receipt':
                return $this->payReceiptAction($codes, $model, $allowUpdate, $this->dataBase, $this->user->nick);

            case 'unlink-file':
                return $this->unlinkFileAction();

            case 'sort-files':
                return $this->sortFilesAction();
        }

        return parent::execPreviousAction($action);
    }

    /**
     * Customer special fields
     *
     * @return array
     */
    private function getCustomerFields(): array
    {
        return [
            'linkfield' => 'customer',
            'numfield' => 'numero2',
            'numtitle' => 'number2'
        ];
    }

    /**
     * Supplier special fields
     *
     * @return array
     */
    private function getSupplierFields(): array
    {
        return [
            'linkfield' => 'supplier',
            'numfield' => 'numproveedor',
            'numtitle' => 'numsupplier'
        ];
    }

    /**
     * Load view data
     *
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mvn = $this->getMainViewName();

        switch ($viewName) {
            case $mvn:
                parent::loadData($viewName, $view);
                $this->setCustomWidgetValues($viewName);
                if ($view->model->exists() && !empty($view->model->cifnif)) {
                    $view->addButton([
                        'action' => 'check-vies',
                        'color' => 'info',
                        'icon' => 'fa-solid fa-check-double',
                        'label' => 'check-vies'
                    ]);
                }
                break;

            case 'docfiles':
                $this->loadDataDocFiles($view, $this->getModelClassName(), $this->getModel()->primaryColumnValue());
                break;

            case 'ListSubcuenta':
                $codsubcuenta = $this->getViewModelValue($mvn, 'codsubcuenta');
                $where = [new DataBaseWhere('codsubcuenta', $codsubcuenta)];
                $view->loadData('', $where);
                $this->setSettings($viewName, 'active', $view->count > 0);
                break;

            case 'ListEmailSent':
                $email = $this->getViewModelValue($mvn, 'email');
                if (empty($email)) {
                    $this->setSettings($viewName, 'active', false);
                    break;
                }

                $where = [new DataBaseWhere('addressee', $email)];
                $view->loadData('', $where);

                // añadimos un botón para enviar un nuevo email
                $view->addButton([
                    'action' => 'SendMail?email-to=' . $email,
                    'color' => 'success',
                    'icon' => 'fa-solid fa-envelope',
                    'label' => 'send',
                    'type' => 'link'
                ]);
                break;
        }
    }
}
