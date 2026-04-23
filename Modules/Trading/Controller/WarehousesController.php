<?php

namespace Modules\Trading\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Alxarafe\ResourceController\Component\Fields;
use Modules\Trading\Model\Warehouse;

class WarehousesController extends ResourceController
{
    public static function getModuleName(): string
    {
        return 'Trading';
    }

    public static function getControllerName(): string
    {
        return 'Warehouses';
    }

    protected function getModelClassName(): string
    {
        return Warehouse::class;
    }

    public function index(): void
    {
        parent::index();
    }

    protected function getListColumns(): array
    {
        return [
            new Fields\Text('codalmacen', 'code'),
            new Fields\Text('nombre', 'name'),
            new Fields\Boolean('activo', 'active'),
            new Fields\Text('ciudad', 'city'),
            new Fields\Text('telefono', 'phone'),
        ];
    }

    protected function getEditFields(): array
    {
        return [
            new Fields\Text('codalmacen', 'code', ['required' => true]),
            new Fields\Text('nombre', 'name', ['required' => true]),
            new Fields\Boolean('activo', 'active'),
            new Fields\Textarea('direccion', 'address'),
            new Fields\Text('codpostal', 'zip_code'),
            new Fields\Text('ciudad', 'city'),
            new Fields\Text('provincia', 'province'),
            new Fields\Text('codpais', 'country_code'),
            new Fields\Text('telefono', 'phone'),
        ];
    }
}
