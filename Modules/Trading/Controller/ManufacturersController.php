<?php

namespace Modules\Trading\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Alxarafe\ResourceController\Component\Fields;
use Modules\Trading\Model\Manufacturer;

class ManufacturersController extends ResourceController
{
    public static function getModuleName(): string
    {
        return 'Trading';
    }

    public static function getControllerName(): string
    {
        return 'Manufacturers';
    }

    protected function getModelClassName(): string
    {
        return Manufacturer::class;
    }

    public function getPageData(): array
    {
        return [
            'name'       => 'ListFabricante',
            'title'      => 'manufacturers',
            'icon'       => 'fa-solid fa-industry',
            'menu'       => 'warehouse',
            'submenu'    => null,
            'showonmenu' => true,
            'ordernum'   => 100,
        ];
    }

    protected function getListColumns(): array
    {
        return [
            new Fields\Text('codfabricante', 'Code'),
            new Fields\Text('nombre', 'Name'),
            new Fields\Integer('numproductos', 'Products'),
        ];
    }

    protected function getEditFields(): array
    {
        return [
            new Fields\Text('codfabricante', 'Code', ['required' => true]),
            new Fields\Text('nombre', 'Name', ['required' => true]),
        ];
    }
}
