<?php

namespace Modules\Trading\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Alxarafe\ResourceController\Component\Container\Panel;
use Alxarafe\ResourceController\Component\Container\Row;
use Alxarafe\ResourceController\Component\Fields\Decimal;
use Alxarafe\ResourceController\Component\Fields\Text;
use Modules\Trading\Model\Currency;

class CurrenciesController extends ResourceController
{
    protected function getModelClassName(): string
    {
        return Currency::class;
    }

    public function getEditFields(): array
    {
        return [
            new Panel('currency_details', [
                new Row([
                    new Text('coddivisa', 'code', ['col' => 'col-md-3', 'required' => true]),
                    new Text('descripcion', 'description', ['col' => 'col-md-5', 'required' => true]),
                    new Text('codiso', 'iso_code', ['col' => 'col-md-2']),
                    new Text('simbolo', 'symbol', ['col' => 'col-md-2']),
                ], ['col' => 'col-12', 'class' => 'mb-3']),
                new Row([
                    new Decimal('tasaconv', 'conversion_rate', ['col' => 'col-md-6', 'precision' => 6]),
                    new Decimal('tasaconvcompra', 'purchase_conversion_rate', ['col' => 'col-md-6', 'precision' => 6]),
                ], ['col' => 'col-12']),
            ], ['class' => 'shadow-sm border-primary'])
        ];
    }

    protected function getListColumns(): array
    {
        return [
            new Text('coddivisa', 'code'),
            new Text('descripcion', 'description'),
            new Text('codiso', 'iso_code'),
            new Text('simbolo', 'symbol'),
            new Decimal('tasaconv', 'conversion_rate'),
        ];
    }
}
