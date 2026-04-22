<?php

namespace Modules\Trading\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Alxarafe\ResourceController\Component\Container\Panel;
use Alxarafe\ResourceController\Component\Container\Row;
use Alxarafe\ResourceController\Component\Container\Tab;
use Alxarafe\ResourceController\Component\Container\TabGroup;
use Alxarafe\ResourceController\Component\Fields\Integer;
use Alxarafe\ResourceController\Component\Fields\Text;
use Modules\Trading\Model\Family;

/**
 * Families CRUD controller for the Trading module.
 *
 * Extends ResourceController which auto-generates list and edit views
 * from the model's field metadata. No manual Blade views needed.
 */
class FamiliesController extends ResourceController
{
    protected function getModelClassName(): string
    {
        return Family::class;
    }

    public function getEditFields(): array
    {
        return [
            new Tab('general', 'general_info', 'fas fa-info-circle', [
                new Panel('family_details', [
                    new Row([
                        new Text('codfamilia', 'code', ['col' => 'col-md-3', 'required' => true]),
                        new Text('descripcion', 'description', ['col' => 'col-md-6', 'required' => true]),
                        new Integer('numproductos', 'product_count', ['col' => 'col-md-3', 'readonly' => true]),
                    ], ['col' => 'col-12', 'class' => 'mb-3']),
                    new Row([
                        new Text('madre', 'parent_family', ['col' => 'col-md-12']),
                    ], ['col' => 'col-12']),
                ], ['class' => 'shadow-sm border-primary']),
            ]),
            new Tab('accounting', 'accounting', 'fas fa-file-invoice-dollar', [
                new Panel('accounting_codes', [
                    new Row([
                        new Text('codsubcuentacom', 'purchase_account', ['col' => 'col-md-4']),
                        new Text('codsubcuentaven', 'sales_account', ['col' => 'col-md-4']),
                        new Text('codsubcuentairpfcom', 'irpf_account', ['col' => 'col-md-4']),
                    ], ['col' => 'col-12']),
                ], ['class' => 'shadow-sm border-secondary']),
            ]),
        ];
    }

    protected function getListColumns(): array
    {
        return [
            new Text('codfamilia', 'code'),
            new Text('descripcion', 'description'),
            new Text('madre', 'parent_family'),
            new Integer('numproductos', 'product_count'),
        ];
    }
}
