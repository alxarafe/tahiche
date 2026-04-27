<?php
namespace FacturaScripts\Plugins\Accounting\Extension\Controller;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

class EditEmpresa
{
    public function createViews(): Closure
    {
        return function () {
            $this->addListView('ListEjercicio', 'Ejercicio', 'exercises', 'fa-solid fa-calendar-alt')
                ->disableColumn('company');
        };
    }

    public function loadData(): Closure
    {
        return function ($viewName, $view) {
            if ($viewName === 'ListEjercicio') {
                $id = $this->getViewModelValue($this->getMainViewName(), 'idempresa');
                $where = [new DataBaseWhere('idempresa', $id)];
                $view->loadData('', $where);
            }
        };
    }
}
