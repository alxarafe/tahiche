<?php
namespace FacturaScripts\Plugins\Trading\Extension\Controller;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

class EditEmpresa
{
    public function createViews(): Closure
    {
        return function () {
            $this->addListView('EditAlmacen', 'Almacen', 'warehouses', 'fa-solid fa-warehouse')
                ->disableColumn('company');
        };
    }

    public function loadData(): Closure
    {
        return function ($viewName, $view) {
            if ($viewName === 'EditAlmacen') {
                $id = $this->getViewModelValue($this->getMainViewName(), 'idempresa');
                $where = [new DataBaseWhere('idempresa', $id)];
                $view->loadData('', $where);
            }
        };
    }
}
