<?php
declare(strict_types=1);

namespace Modules\Barcodes\Extension\Controller;

use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;

class EditProducto
{
    public function createViews()
    {
        return function () {
            /** @var EditController $this */
            // @phpstan-ignore-next-line
            $this->addEditListView('EditProductBarcode', 'ProductBarcode', 'barcode', 'fas fa-barcode');
        };
    }

    public function loadData()
    {
        return function ($viewName, $view) {
            /** @var EditController $this */
            if ($viewName === 'EditProductBarcode') {
                $idproducto = $this->getViewModelValue('EditProducto', 'idproducto');

                // Get all variants for this product
                $variantes = \FacturaScripts\Plugins\Trading\Model\Variante::all([\FacturaScripts\Core\Where::eq('idproducto', $idproducto)]);
                $variantIds = [];
                foreach ($variantes as $v) {
                    $variantIds[] = $v->idvariante;
                }

                if (empty($variantIds)) {
                    $variantIds = [-1]; // Prevent SQL error if no variants
                }

                $view->loadData('', [\FacturaScripts\Core\Where::in('idvariante', $variantIds)]);
            }
        };
    }
}
