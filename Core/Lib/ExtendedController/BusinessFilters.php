<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Dinamic\DataSrc\Agentes;
use FacturaScripts\Dinamic\DataSrc\Almacenes;
use FacturaScripts\Dinamic\DataSrc\Divisas;
use FacturaScripts\Dinamic\DataSrc\Ejercicios;
use FacturaScripts\Dinamic\DataSrc\Empresas;
use FacturaScripts\Dinamic\DataSrc\FormasPago;
use FacturaScripts\Dinamic\DataSrc\GruposClientes;
use FacturaScripts\Dinamic\DataSrc\Impuestos;
use FacturaScripts\Dinamic\DataSrc\Series;
use FacturaScripts\Core\Lib\InvoiceOperation;
use FacturaScripts\Core\Tools;

/**
 * Centralizes the creation of common business filters for list views.
 * Handles class_exists() checks internally so controllers don't crash
 * when optional plugins are disabled.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
final class BusinessFilters
{
    /**
     * Get codeModel data from a DataSrc class, returning empty array if the class doesn't exist.
     */
    private static function safeCodeModel(string $class): array
    {
        return class_exists($class) ? $class::codeModel() : [];
    }

    /**
     * Add company filter if there are multiple companies.
     */
    public static function addCompanyFilter(ListView $view): void
    {
        $items = self::safeCodeModel(Empresas::class);
        if (count($items) > 2) {
            $view->addFilterSelect('idempresa', 'company', 'idempresa', $items);
        }
    }

    /**
     * Add warehouse filter if there are multiple warehouses.
     */
    public static function addWarehouseFilter(ListView $view): void
    {
        $items = self::safeCodeModel(Almacenes::class);
        if (count($items) > 2) {
            $view->addFilterSelect('codalmacen', 'warehouse', 'codalmacen', $items);
        }
    }

    /**
     * Add series filter if there are multiple series.
     */
    public static function addSeriesFilter(ListView $view): void
    {
        $items = self::safeCodeModel(Series::class);
        if (count($items) > 2) {
            $view->addFilterSelect('codserie', 'series', 'codserie', $items);
        }
    }

    /**
     * Add exercise filter if there are multiple exercises.
     */
    public static function addExerciseFilter(ListView $view): void
    {
        $items = self::safeCodeModel(Ejercicios::class);
        if (count($items) > 2) {
            $view->addFilterSelect('codejercicio', 'exercise', 'codejercicio', $items);
        }
    }

    /**
     * Add payment method filter if there are multiple payment methods.
     */
    public static function addPaymentMethodFilter(ListView $view): void
    {
        $items = self::safeCodeModel(FormasPago::class);
        if (count($items) > 2) {
            $view->addFilterSelect('codpago', 'payment-method', 'codpago', $items);
        }
    }

    /**
     * Add currency filter if there are multiple currencies.
     */
    public static function addCurrencyFilter(ListView $view): void
    {
        $items = self::safeCodeModel(Divisas::class);
        if (count($items) > 2) {
            $view->addFilterSelect('coddivisa', 'currency', 'coddivisa', $items);
        }
    }

    /**
     * Add agent filter if there are multiple agents.
     */
    public static function addAgentFilter(ListView $view): void
    {
        $items = self::safeCodeModel(Agentes::class);
        if (count($items) > 1) {
            $view->addFilterSelect('codagente', 'agent', 'codagente', $items);
        }
    }

    /**
     * Add tax filter.
     */
    public static function addTaxFilter(ListView $view): void
    {
        $items = self::safeCodeModel(Impuestos::class);
        $view->addFilterSelect('codimpuesto', 'tax', 'codimpuesto', $items);
    }

    /**
     * Add invoice operation filter.
     */
    public static function addOperationFilter(ListView $view): void
    {
        $operations = [['code' => '', 'description' => '------']];
        if (class_exists(InvoiceOperation::class)) {
            foreach (InvoiceOperation::all() as $key => $value) {
                $operations[] = [
                    'code' => $key,
                    'description' => Tools::trans($value),
                ];
            }
        }
        $view->addFilterSelect('operacion', 'operation', 'operacion', $operations);
    }

    /**
     * Add user filter.
     *
     * @param ListView $view
     * @param \FacturaScripts\Core\Model\CodeModel $codeModel
     */
    public static function addUserFilter(ListView $view, $codeModel): void
    {
        $users = $codeModel->all('users', 'nick', 'nick');
        if (count($users) > 1) {
            $view->addFilterSelect('nick', 'user', 'nick', $users);
        }
    }

    /**
     * Add the standard set of business document filters (company, warehouse, series,
     * operation, payment method, currency). This replaces the duplicated
     * addCommonViewFilters() code in ListBusinessDocument and ComercialContactController.
     *
     * @param ListView $view
     * @param \FacturaScripts\Core\Model\CodeModel $codeModel
     * @param string $modelName Document type name for status filter
     * @param bool $onlyOwnerData Whether the user has onlyOwnerData permission
     */
    public static function addDocumentFilters(
        ListView $view,
        $codeModel,
        string $modelName,
        bool $onlyOwnerData = false
    ): void {
        // period and total range
        $view->addFilterPeriod('date', 'period', 'fecha')
            ->addFilterNumber('min-total', 'total', 'total', '>=')
            ->addFilterNumber('max-total', 'total', 'total', '<=');

        // document status
        $where = [new \FacturaScripts\Core\Base\DataBase\DataBaseWhere('tipodoc', $modelName)];
        $statusValues = $codeModel->all('estados_documentos', 'idestado', 'nombre', true, $where);
        $view->addFilterSelect('idestado', 'state', 'idestado', $statusValues);

        // user
        if ($onlyOwnerData === false) {
            self::addUserFilter($view, $codeModel);
        }

        // standard business filters
        self::addCompanyFilter($view);
        self::addWarehouseFilter($view);
        self::addSeriesFilter($view);
        self::addOperationFilter($view);
        self::addPaymentMethodFilter($view);
        self::addCurrencyFilter($view);

        // checkboxes
        $view->addFilterCheckbox('totalrecargo', 'surcharge', 'totalrecargo', '!=', 0)
            ->addFilterCheckbox('totalirpf', 'retention', 'totalirpf', '!=', 0)
            ->addFilterCheckbox('totalsuplidos', 'supplied-amount', 'totalsuplidos', '!=', 0)
            ->addFilterCheckbox('numdocs', 'has-attachments', 'numdocs', '!=', 0);
    }
}
