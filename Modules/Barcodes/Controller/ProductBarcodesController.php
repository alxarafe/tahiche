<?php

declare(strict_types=1);

namespace Modules\Barcodes\Controller;

use Alxarafe\ResourceController\Component\Fields\Hidden;
use Alxarafe\ResourceController\Component\Fields\Text;
use Alxarafe\ResourceController\Component\Fields\Select;
use Alxarafe\ResourceController\Component\Fields\Textarea;
use Tahiche\Infrastructure\Component\Field\Number;
use FacturaScripts\Core\Tools;
use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Barcodes\Model\ProductBarcode;

class ProductBarcodesController extends ResourceController
{
    use \Tahiche\Infrastructure\Http\LegacyBridgeTrait;

    protected function getModelClassName(): string
    {
        return ProductBarcode::class;
    }

    public function getListColumns(): array
    {
        return ['codbarras', 'tipo', 'cantidad', 'descripcion'];
    }

    public function getEditFields(): array
    {
        $tipos = ProductBarcode::barcodeTypes();

        return [
            'barcode' => [
                'label' => Tools::trans('barcode'),
                'icon' => 'fas fa-barcode',
                'fields' => [
                    new Hidden('id', 'ID'),
                    new Hidden('idproducto', 'ID Producto'),
                    new Text('codbarras', Tools::trans('barcode'), [
                        'col' => 6,
                        'maxlength' => 128,
                        'required' => true,
                        'placeholder' => 'Ej: 5449000000996',
                    ]),
                    new Select('tipo', Tools::trans('type'), $tipos, [
                        'col' => 3,
                        'required' => true,
                    ]),
                    new Number('cantidad', Tools::trans('quantity'), [
                        'col' => 3,
                        'min' => 0.01,
                        'step' => 0.01,
                    ]),
                    new Textarea('descripcion', Tools::trans('description'), [
                        'col' => 12,
                        'rows' => 2,
                    ]),
                ],
            ],
        ];
    }
}
