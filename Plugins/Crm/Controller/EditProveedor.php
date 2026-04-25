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
use FacturaScripts\Plugins\Trading\Controller\EditProveedor as TradingEditProveedor;

/**
 * Controller extension to add contacts view to EditProveedor in CRM
 */
class EditProveedor extends TradingEditProveedor
{
    protected function createViews(): void
    {
        parent::createViews();
        $this->createContactsView();
    }

    protected function loadData($viewName, $view)
    {
        $mainViewName = $this->getMainViewName();
        $codproveedor = $this->getViewModelValue($mainViewName, 'codproveedor');
        $where = [new DataBaseWhere('codproveedor', $codproveedor)];

        if ($viewName === 'EditDireccionContacto') {
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
            $this->views[$viewName]->disableColumn('contact');
            return;
        }

        // Search for supplier contacts
        $codproveedor = $this->getViewModelValue($viewName, 'codproveedor');
        $where = [new DataBaseWhere('codproveedor', $codproveedor)];
        $contacts = $this->codeModel->all('contactos', 'idcontacto', 'descripcion', false, $where);

        // Load values option to default contact
        $columnBilling = $this->views[$viewName]->columnForName('contact');
        if ($columnBilling && $columnBilling->widget->getType() === 'select') {
            $columnBilling->widget->setValuesFromCodeModel($contacts);
        }
    }
}
