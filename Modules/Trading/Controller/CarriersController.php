<?php

namespace Modules\Trading\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Alxarafe\ResourceController\Component\Container\Panel;
use Alxarafe\ResourceController\Component\Container\Row;
use Alxarafe\ResourceController\Component\Fields\Boolean;
use Alxarafe\ResourceController\Component\Fields\Text;
use Modules\Trading\Model\Carrier;

class CarriersController extends ResourceController
{
    protected function getModelClassName(): string
    {
        return Carrier::class;
    }

    public function getEditFields(): array
    {
        return [
            new Panel('carrier_details', [
                new Row([
                    new Text('codtrans', 'code', ['col' => 'col-md-3', 'required' => true]),
                    new Text('nombre', 'name', ['col' => 'col-md-7', 'required' => true]),
                    new Boolean('activo', 'active', ['col' => 'col-md-2']),
                ], ['col' => 'col-12', 'class' => 'mb-3']),
                new Row([
                    new Text('telefono', 'phone', ['col' => 'col-md-6']),
                    new Text('web', 'website', ['col' => 'col-md-6']),
                ], ['col' => 'col-12']),
            ], ['class' => 'shadow-sm border-info'])
        ];
    }

    protected function getListColumns(): array
    {
        return [
            new Text('codtrans', 'code'),
            new Text('nombre', 'name'),
            new Text('telefono', 'phone'),
            new Text('web', 'website'),
            new Boolean('activo', 'active'),
        ];
    }
}
