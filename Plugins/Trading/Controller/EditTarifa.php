<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Tools;

/**
 * Controller to edit a single item from the Tarifa model
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author jhonsmall                     <juancarloschico0@gmail.com>
 */
class EditTarifa extends EditController
{
    public function getModelClassName(): string
    {
        return 'Tarifa';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'rate';
        $data['icon'] = 'fa-solid fa-percentage';
        return $data;
    }


    protected function createProductView(string $viewName = 'ListTarifaProducto'): void
    {
        $this->addListView($viewName, 'Join\TarifaProducto', 'products', 'fa-solid fa-cubes')
            ->addOrderBy(['coste'], 'cost-price')
            ->addOrderBy(['descripcion'], 'description')
            ->addOrderBy(['precio'], 'price')
            ->addOrderBy(['referencia'], 'reference', 1)
            ->addSearchFields(['variantes.referencia', 'descripcion'])
            ->setSettings('btnDelete', false)
            ->setSettings('btnNew', false)
            ->setSettings('checkBoxes', false);
    }

    /**
     * Creates tabs or views.
     */
    protected function createViews()
    {
        parent::createViews();

        $this->setTabsPosition('bottom');

        $this->createProductView();
    }

    /**
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {

            case 'ListTarifaProducto':
                $codtarifa = $this->getViewModelValue($this->getMainViewName(), 'codtarifa');
                $where = [new DataBaseWhere('codtarifa', $codtarifa)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

}
