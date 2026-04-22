<?php

/*
 * Copyright (C) 2024-2026 Rafael San José <rsanjose@alxarafe.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Modules\Sales\Controller;

use Tahiche\Infrastructure\Http\ResourceController;
use Alxarafe\ResourceController\Component\Container\TabGroup;
use Alxarafe\ResourceController\Component\Container\Tab;
use Alxarafe\ResourceController\Component\Container\Panel;
use Alxarafe\ResourceController\Component\Container\Row;
use Alxarafe\ResourceController\Component\Fields\Text;
use Alxarafe\ResourceController\Component\Fields\Date;
use Alxarafe\ResourceController\Component\Fields\Decimal;
use Modules\Sales\Model\SalesInvoice;
use Modules\Sales\Model\SalesInvoiceLine;
use Tahiche\Infrastructure\Component\Container\DetailLines;
use Alxarafe\ResourceController\Component\Fields\Select2;
use Illuminate\Database\Capsule\Manager as DB;

class SalesInvoicesController extends ResourceController
{
    #[\Override]
    protected function getModelClassName(): string
    {
        return SalesInvoice::class;
    }

    #[\Override]
    public static function getModuleName(): string
    {
        return 'Sales';
    }

    #[\Override]
    public static function getControllerName(): string
    {
        return 'SalesInvoices';
    }

    #[\Override]
    public function getEditFields(): array
    {
        $clientes = [];
        try {
            foreach (DB::select('SELECT codcliente, nombre FROM clientes ORDER BY nombre') as $row) {
                $clientes[$row->codcliente] = $row->nombre;
            }
        } catch (\Throwable $e) {}

        $series = [];
        try {
            foreach (DB::select('SELECT codserie, descripcion FROM series ORDER BY descripcion') as $row) {
                $series[$row->codserie] = $row->descripcion;
            }
        } catch (\Throwable $e) {}

        $formasPago = [];
        try {
            foreach (DB::select('SELECT codpago, descripcion FROM formaspago ORDER BY descripcion') as $row) {
                $formasPago[$row->codpago] = $row->descripcion;
            }
        } catch (\Throwable $e) {}

        return [
            new Tab('general', 'general_info', 'fas fa-info-circle', [
                new Panel('header', [
                    new Row([
                        new Select2('codcliente', 'customer', $clientes, ['col' => 'col-md-6', 'required' => true]),
                        new Date('fecha', 'date', ['col' => 'col-md-3', 'required' => true]),
                        new Select2('codserie', 'series', $series, ['col' => 'col-md-3', 'required' => true]),
                    ], ['col' => 'col-12', 'class' => 'mb-3']),
                    new Row([
                        new Text('codigo', 'code', ['col' => 'col-md-3', 'readonly' => true]),
                        new Select2('codpago', 'payment_method', $formasPago, ['col' => 'col-md-3']),
                        new Text('observaciones', 'observations', ['col' => 'col-md-6']),
                    ], ['col' => 'col-12']),
                ], ['class' => 'shadow-sm border-primary', 'full_width' => true]),
            ]),
            new Tab('lines', 'lines', 'fas fa-list', [
                new DetailLines('getLines', 'document_lines', [
                    new Text('referencia', 'reference', ['col' => 'col-md-2']),
                    new Text('descripcion', 'description', ['col' => 'col-md-4']),
                    new Decimal('cantidad', 'quantity', ['col' => 'col-md-1']),
                    new Decimal('pvpunitario', 'price', ['col' => 'col-md-2']),
                    new Decimal('dtopor', 'discount', ['col' => 'col-md-1']),
                    new Decimal('iva', 'tax', ['col' => 'col-md-1']),
                    new Decimal('pvptotal', 'total', ['col' => 'col-md-1', 'readonly' => true])
                ], [
                    'model' => SalesInvoiceLine::class,
                    'foreignKey' => 'idfactura',
                    'sortable' => true,
                    'addRow' => true,
                    'removeRow' => true,
                    'autoRecalculate' => true,
                    'footerTotals' => ['pvptotal' => 'sum', 'cantidad' => 'sum']
                ]),
            ]),
            new Tab('totals', 'totals', 'fas fa-calculator', [
                new Panel('totals_panel', [
                    new Row([
                        new Decimal('neto', 'net', ['col' => 'col-md-3', 'readonly' => true]),
                        new Decimal('totaliva', 'total_tax', ['col' => 'col-md-3', 'readonly' => true]),
                        new Decimal('totalrecargo', 'surcharge', ['col' => 'col-md-3', 'readonly' => true]),
                        new Decimal('total', 'total', ['col' => 'col-md-3', 'readonly' => true]),
                    ])
                ], ['class' => 'shadow-sm border-success', 'full_width' => true])
            ])
        ];
    }
    
    #[\Override]
    protected function getListColumns(): array
    {
        return [
            new Text('codigo', 'code'),
            new Date('fecha', 'date'),
            new Text('codcliente', 'customer'),
            new Text('nombre', 'name'),
            new Decimal('total', 'total'),
        ];
    }
}
