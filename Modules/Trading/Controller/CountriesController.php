<?php

namespace Modules\Trading\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Alxarafe\ResourceController\Component\Fields;
use Modules\Trading\Model\Country;

class CountriesController extends ResourceController
{
    public static function getModuleName(): string
    {
        return 'Trading';
    }

    public static function getControllerName(): string
    {
        return 'Countries';
    }

    protected function getModelClassName(): string
    {
        return Country::class;
    }

    public function index(): void
    {
        $this->privateCore();
    }

    protected function getListColumns(): array
    {
        return [
            new Fields\Text('codpais', 'code'),
            new Fields\Text('nombre', 'name'),
            new Fields\Text('codiso', 'iso_code'),
        ];
    }

    protected function getEditFields(): array
    {
        return [
            new Fields\Text('codpais', 'code', ['required' => true]),
            new Fields\Text('nombre', 'name', ['required' => true]),
            new Fields\Text('codiso', 'iso_code'),
        ];
    }
}
