<?php

namespace Modules\Trading\Controller;

use Alxarafe\ResourceController\Component\Fields\Boolean;
use Alxarafe\ResourceController\Component\Fields\Date;
use Alxarafe\ResourceController\Component\Fields\DateTime;
use Alxarafe\ResourceController\Component\Fields\Decimal;
use Alxarafe\ResourceController\Component\Fields\Hidden;
use Alxarafe\ResourceController\Component\Fields\Integer;
use Alxarafe\ResourceController\Component\Fields\Select;
use Alxarafe\ResourceController\Component\Fields\StaticText;
use Alxarafe\ResourceController\Component\Fields\Text;
use Alxarafe\ResourceController\Component\Fields\Textarea;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use Tahiche\Infrastructure\Http\ResourceController;
use Modules\Trading\Model\Product;

class ProductsController extends ResourceController
{
    use \Tahiche\Infrastructure\Http\LegacyBridgeTrait;

    private bool $legacyApplied = false;

    protected function getModelClassName(): string
    {
        return Product::class;
    }

    public function getListColumns(): array
    {
        return ['referencia', 'descripcion', 'precio', 'stockfis'];
    }

    public function getEditFields(): array
    {
        // --- Cargar pestañas inyectadas por plugins legacy PRIMERO ---
        if (!$this->legacyApplied) {
            $this->legacyApplied = true;
            $this->applyLegacyExtensions('EditProducto');
        }

        $id = $_GET['code'] ?? $_GET['id'] ?? null;
        $badges = $this->getTabBadges();

        // --- Pestaña Producto ---
        $familias = $this->getSelectOptions('\\FacturaScripts\\Dinamic\\Model\\Familia', 'codfamilia', 'descripcion');
        $fabricantes = $this->getSelectOptions('\\FacturaScripts\\Dinamic\\Model\\Fabricante', 'codfabricante', 'nombre');
        $impuestos = $this->getSelectOptions('\\FacturaScripts\\Dinamic\\Model\\Impuesto', 'codimpuesto', 'descripcion');

        $tabs = [
            'producto' => [
                'label' => Tools::trans('product'),
                'icon' => 'fas fa-cube',
                'fields' => [
                    new Text('referencia', Tools::trans('reference'), ['col' => 4, 'maxlength' => 30]),
                    new Text('descripcion', Tools::trans('description'), ['col' => 8]),
                    new Select('codfamilia', Tools::trans('family'), $familias, ['col' => 4]),
                    new Select('codfabricante', Tools::trans('manufacturer'), $fabricantes, ['col' => 4]),
                    new Select('codimpuesto', Tools::trans('tax'), $impuestos, ['col' => 4]),
                    new Decimal('precio', Tools::trans('price'), ['col' => 3]),
                    new Decimal('stockfis', Tools::trans('stock'), ['col' => 3, 'readonly' => true]),
                    new Boolean('sevende', Tools::trans('for-sale'), ['col' => 2]),
                    new Boolean('secompra', Tools::trans('for-purchase'), ['col' => 2]),
                    new Boolean('bloqueado', Tools::trans('locked'), ['col' => 2]),
                    new Boolean('nostock', Tools::trans('no-stock'), ['col' => 2]),
                    new Boolean('publico', Tools::trans('public'), ['col' => 2]),
                    new Boolean('ventasinstock', Tools::trans('allow-sale-without-stock'), ['col' => 2]),
                    new Textarea('observaciones', Tools::trans('observations'), ['col' => 12]),
                    new Hidden('idproducto', 'ID'),
                    new Date('fechaalta', Tools::trans('creation-date'), ['col' => 3, 'readonly' => true]),
                    new DateTime('actualizado', Tools::trans('last-update'), ['col' => 3, 'readonly' => true]),
                ]
            ],
        ];

        // --- Pestañas de datos relacionados (solo en modo edición con ID) ---
        if ($id) {
            $tabs['variantes'] = [
                'label' => Tools::trans('variants'),
                'icon' => 'fas fa-project-diagram',
                'badge' => $this->getBadgeCount('variantes', $badges),
                'fields' => $this->buildRelatedTable('\\FacturaScripts\\Dinamic\\Model\\Variante', 'idproducto', $id),
            ];

            $tabs['stock'] = [
                'label' => Tools::trans('stock'),
                'icon' => 'fas fa-dolly',
                'badge' => $this->getBadgeCount('stock', $badges),
                'fields' => $this->buildRelatedTable('\\FacturaScripts\\Dinamic\\Model\\Stock', 'idproducto', $id),
            ];

            $tabs['proveedores'] = [
                'label' => Tools::trans('suppliers'),
                'icon' => 'fas fa-users',
                'badge' => $this->getBadgeCount('proveedores', $badges),
                'fields' => $this->buildRelatedTable('\\FacturaScripts\\Dinamic\\Model\\ProductoProveedor', 'idproducto', $id),
            ];
        }

        // --- Pestañas inyectadas por plugins legacy ---

        foreach ($this->legacyTabs as $tab) {
            if (empty($tab['label'])) {
                continue;
            }

            $tabs[$tab['id']] = [
                'label' => Tools::trans($tab['label']),
                'icon' => $tab['icon'],
                'badge' => $this->getBadgeCount($tab['id'], $badges),
                'fields' => $this->buildRelatedTable(
                    "\\FacturaScripts\\Dinamic\\Model\\" . $tab['label'],
                    'idproducto',
                    $id
                ),
            ];
        }

        // Botones legacy
        foreach ($this->legacyButtons as $btn) {
            $this->structConfig['edit']['head_buttons'][] = [
                'label' => Tools::trans($btn['label'] ?? $btn['action']),
                'icon' => $btn['icon'] ?? '',
                'type' => $btn['color'] ?? 'warning',
                'action' => $btn['action'],
                'name' => 'legacy-' . $btn['action'],
            ];
        }

        return $tabs;
    }

    public function getTabBadges(): array
    {
        $id = $_GET['code'] ?? $_GET['id'] ?? null;
        if (!$id) {
            return [];
        }

        $badges = [
            'variantes' => fn() => (new \FacturaScripts\Core\Model\Variante())->count([\FacturaScripts\Core\Where::eq('idproducto', $id)]),
            'stock' => fn() => (new \FacturaScripts\Core\Model\Stock())->count([\FacturaScripts\Core\Where::eq('idproducto', $id)]),
            'proveedores' => fn() => (new \FacturaScripts\Core\Model\ProductoProveedor())->count([\FacturaScripts\Core\Where::eq('idproducto', $id)]),
        ];

        // Añadimos badges dinámicos para las pestañas de los plugins legacy
        foreach ($this->legacyTabs as $tab) {
            if (empty($tab['label'])) {
                continue;
            }
            $modelClass = "\\FacturaScripts\\Dinamic\\Model\\" . $tab['label'];
            if (class_exists($modelClass)) {
                $badges[$tab['id']] = fn() => (new $modelClass())->count([\FacturaScripts\Core\Where::eq('idproducto', $id)]);
            }
        }

        return $badges;
    }

    // --- Helpers reutilizables ---

    /**
     * Devuelve la cantidad para el badge si count > 0
     */
    private function getBadgeCount(string $key, array $badges): ?int
    {
        if (isset($badges[$key])) {
            $count = call_user_func($badges[$key]);
            if ($count > 0) {
                return (int)$count;
            }
        }
        return null;
    }

    /**
     * Genera las opciones de un Select a partir de un modelo.
     */
    private function getSelectOptions(string $modelClass, string $valueField, string $labelField): array
    {
        $options = ['' => '------'];
        if (!class_exists($modelClass)) {
            return $options;
        }

        foreach ((new $modelClass())->all([], [$labelField => 'ASC'], 0, 0) as $item) {
            $options[$item->{$valueField}] = $item->{$labelField};
        }
        return $options;
    }

    /**
     * Genera un campo StaticText con una tabla HTML de registros relacionados,
     * metida dentro de una tarjeta con márgenes.
     */
    private function buildRelatedTable(string $modelClass, string $foreignKey, $foreignValue): array
    {
        // Envolvemos en un contenedor con márgenes (mt-3 mb-4) y padding (p-3)
        $html = '<div class="card shadow-sm border-0 mt-3 mb-4"><div class="card-body p-0">';

        if (!class_exists($modelClass) || empty($foreignValue)) {
            $html .= '<div class="alert alert-info m-3 mb-3">' . Tools::trans('no-data') . '</div></div></div>';
            return [new StaticText($html)];
        }

        $records = (new $modelClass())->all([Where::eq($foreignKey, $foreignValue)]);
        if (empty($records)) {
            $html .= '<div class="alert alert-info m-3 mb-3">' . Tools::trans('no-data') . '</div></div></div>';
            return [new StaticText($html)];
        }

        $html .= '<div class="table-responsive m-0"><table class="table table-sm table-striped table-hover mb-0">';
        $html .= '<thead class="table-light"><tr>';
        foreach (get_object_vars($records[0]) as $key => $val) {
            if (!str_starts_with($key, '_')) {
                $html .= '<th class="px-3 py-2 text-muted">' . htmlspecialchars(Tools::trans($key)) . '</th>';
            }
        }
        $html .= '</tr></thead><tbody>';
        foreach ($records as $record) {
            $html .= '<tr>';
            foreach (get_object_vars($record) as $key => $val) {
                if (!str_starts_with($key, '_')) {
                    $html .= '<td class="px-3 py-2 align-middle">' . htmlspecialchars((string)$val) . '</td>';
                }
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';
        $html .= '</div></div>';

        return [new StaticText($html)];
    }
}
