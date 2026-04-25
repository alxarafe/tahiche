<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 */

namespace FacturaScripts\Plugins\Crm\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Paises;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\Trading\Controller\ListProveedor as TradingListProveedor;

/**
 * Controller extension to add contacts view to ListProveedor in CRM
 */
class ListProveedor extends TradingListProveedor
{
    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->createViewAddresses();
    }

    protected function createViewAddresses(string $viewName = 'ListContacto'): void
    {
        $this->addView($viewName, 'Contacto', 'addresses-and-contacts', 'fa-solid fa-address-book')
            ->addOrderBy(['descripcion'], 'description')
            ->addOrderBy(['direccion'], 'address')
            ->addOrderBy(['nombre'], 'name')
            ->addOrderBy(['fechaalta'], 'creation-date', 2)
            ->addSearchFields([
                'apartado', 'apellidos', 'codpostal', 'descripcion', 'direccion', 'email', 'empresa',
                'nombre', 'observaciones', 'telefono1', 'telefono2'
            ]);

        // filtros
        $values = [
            [
                'label' => Tools::trans('suppliers'),
                'where' => [new DataBaseWhere('codproveedor', null, 'IS NOT')]
            ],
            [
                'label' => Tools::trans('all'),
                'where' => []
            ]
        ];
        $this->addFilterSelectWhere($viewName, 'type', $values);

        $cargoValues = $this->codeModel->all('contactos', 'cargo', 'cargo');
        $this->addFilterSelect($viewName, 'cargo', 'position', 'cargo', $cargoValues);

        $this->addFilterSelect($viewName, 'codpais', 'country', 'codpais', Paises::codeModel());

        $provinces = $this->codeModel->all('contactos', 'provincia', 'provincia');
        $this->addFilterSelect($viewName, 'provincia', 'province', 'provincia', $provinces);

        $cities = $this->codeModel->all('contactos', 'ciudad', 'ciudad');
        $this->addFilterSelect($viewName, 'ciudad', 'city', 'ciudad', $cities);

        $this->addFilterAutocomplete($viewName, 'codpostal', 'zip-code', 'codpostal', 'contactos', 'codpostal');

        $this->addFilterCheckbox($viewName, 'verificado', 'verified', 'verificado');

        // desactivamos el mega-search
        $this->setSettings($viewName, 'megasearch', false);
    }
}
