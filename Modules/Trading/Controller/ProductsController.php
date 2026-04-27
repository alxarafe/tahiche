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
use Tahiche\Infrastructure\Http\ResourceController;
use Tahiche\Infrastructure\Adapter\Trading\Product;

class ProductsController extends ResourceController
{
    use \Tahiche\Infrastructure\Bridge\LegacyBridgeTrait;

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
        $familias = $this->getSelectOptions('\\Tahiche\\Infrastructure\\Adapter\\Trading\\Familia', 'codfamilia', 'descripcion');
        $fabricantes = $this->getSelectOptions('\\Tahiche\\Infrastructure\\Adapter\\Trading\\Fabricante', 'codfabricante', 'nombre');
        $impuestos = $this->getSelectOptions('\\Tahiche\\Infrastructure\\Adapter\\Accounting\\Impuesto', 'codimpuesto', 'descripcion');

        $tabs = [
            'producto' => [
                'label' => $this->transLegacy('product'),
                'icon' => 'fas fa-cube',
                'fields' => [
                    new Text('referencia', $this->transLegacy('reference'), ['col' => 4, 'maxlength' => 30]),
                    new Text('descripcion', $this->transLegacy('description'), ['col' => 8]),
                    new Select('codfamilia', $this->transLegacy('family'), $familias, ['col' => 4]),
                    new Select('codfabricante', $this->transLegacy('manufacturer'), $fabricantes, ['col' => 4]),
                    new Select('codimpuesto', $this->transLegacy('tax'), $impuestos, ['col' => 4]),
                    new Decimal('precio', $this->transLegacy('price'), ['col' => 3]),
                    new Decimal('stockfis', $this->transLegacy('stock'), ['col' => 3, 'readonly' => true]),
                    new Boolean('sevende', $this->transLegacy('for-sale'), ['col' => 2]),
                    new Boolean('secompra', $this->transLegacy('for-purchase'), ['col' => 2]),
                    new Boolean('bloqueado', $this->transLegacy('locked'), ['col' => 2]),
                    new Boolean('nostock', $this->transLegacy('no-stock'), ['col' => 2]),
                    new Boolean('publico', $this->transLegacy('public'), ['col' => 2]),
                    new Boolean('ventasinstock', $this->transLegacy('allow-sale-without-stock'), ['col' => 2]),
                    new Textarea('observaciones', $this->transLegacy('observations'), ['col' => 12]),
                    new Hidden('idproducto', 'ID'),
                    new Date('fechaalta', $this->transLegacy('creation-date'), ['col' => 3, 'readonly' => true]),
                    new DateTime('actualizado', $this->transLegacy('last-update'), ['col' => 3, 'readonly' => true]),
                ]
            ],
        ];

        // --- Pestañas de datos relacionados (solo en modo edición con ID) ---
        if ($id) {
            $tabs['variantes'] = [
                'label' => $this->transLegacy('variants'),
                'icon' => 'fas fa-project-diagram',
                'badge' => $this->getBadgeCount('variantes', $badges),
                'fields' => $this->buildRelatedTable('\\Tahiche\\Infrastructure\\Adapter\\Trading\\Variante', 'idproducto', $id),
            ];

            $tabs['stock'] = [
                'label' => $this->transLegacy('stock'),
                'icon' => 'fas fa-dolly',
                'badge' => $this->getBadgeCount('stock', $badges),
                'fields' => $this->buildRelatedTable('\\Tahiche\\Infrastructure\\Adapter\\Trading\\Stock', 'idproducto', $id),
            ];

            $tabs['proveedores'] = [
                'label' => $this->transLegacy('suppliers'),
                'icon' => 'fas fa-users',
                'badge' => $this->getBadgeCount('proveedores', $badges),
                'fields' => $this->buildRelatedTable('\\Tahiche\\Infrastructure\\Adapter\\Trading\\ProductoProveedor', 'idproducto', $id),
            ];
            $tabs['barcodes'] = [
                'label' => $this->transLegacy('barcode'),
                'icon' => 'fas fa-barcode',
                'badge' => $this->getBadgeCount('barcodes', $badges),
                'fields' => $this->getBarcodesFields($id),
            ];
        }

        // --- Pestañas inyectadas por plugins legacy ---

        foreach ($this->legacyTabs as $tab) {
            if (empty($tab['label'])) {
                continue;
            }

            $tabs[$tab['id']] = [
                'label' => $this->transLegacy($tab['label']),
                'icon' => $tab['icon'],
                'badge' => $this->getBadgeCount($tab['id'], $badges),
                'fields' => $this->buildRelatedTable(
                    \FacturaScripts\Core\Internal\ClassResolver::getRealClass("\\FacturaScripts\\Dinamic\\Model\\" . $tab['label']) ?? "\\FacturaScripts\\Dinamic\\Model\\" . $tab['label'],
                    'idproducto',
                    $id
                ),
            ];
        }

        // Botones legacy (evitando duplicados si getEditFields se llama múltiples veces)
        if (!isset($this->structConfig['edit']['head_buttons'])) {
            $this->structConfig['edit']['head_buttons'] = [];
        }

        foreach ($this->legacyButtons as $btn) {
            $btnName = 'legacy-' . $btn['action'];

            // Check if it already exists
            $exists = false;
            foreach ($this->structConfig['edit']['head_buttons'] as $existing) {
                if (($existing['name'] ?? '') === $btnName) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $this->structConfig['edit']['head_buttons'][] = [
                    'label' => $this->transLegacy($btn['label'] ?? $btn['action']),
                    'icon' => $btn['icon'] ?? '',
                    'type' => $btn['color'] ?? 'warning',
                    'action' => $btn['action'],
                    'name' => $btnName,
                ];
            }
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
            'variantes' => fn() => $this->countLegacyRecords(\Tahiche\Infrastructure\Adapter\Trading\Variante::class, 'idproducto', $id),
            'stock' => fn() => $this->countLegacyRecords(\Tahiche\Infrastructure\Adapter\Trading\Stock::class, 'idproducto', $id),
            'proveedores' => fn() => $this->countLegacyRecords(\Tahiche\Infrastructure\Adapter\Trading\ProductoProveedor::class, 'idproducto', $id),
            'barcodes' => fn() => $this->countBarcodes($id),
        ];

        // Añadimos badges dinámicos para las pestañas de los plugins legacy
        foreach ($this->legacyTabs as $tab) {
            if (empty($tab['label'])) {
                continue;
            }
            $badges[$tab['id']] = fn() => $this->getLegacyModelCount($tab['label'], 'idproducto', $id);
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
            $html .= '<div class="alert alert-info m-3 mb-3">' . $this->transLegacy('no-data') . '</div></div></div>';
            return [new StaticText($html)];
        }

        $records = $this->getLegacyRelatedRecords($modelClass, $foreignKey, $foreignValue);
        if (empty($records)) {
            $html .= '<div class="alert alert-info m-3 mb-3">' . $this->transLegacy('no-data') . '</div></div></div>';
            return [new StaticText($html)];
        }

        $html .= '<div class="table-responsive m-0"><table class="table table-sm table-striped table-hover mb-0">';
        $html .= '<thead class="table-light"><tr>';
        foreach (get_object_vars($records[0]) as $key => $val) {
            if (!str_starts_with($key, '_')) {
                $html .= '<th class="px-3 py-2 text-muted">' . htmlspecialchars($this->transLegacy($key)) . '</th>';
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

    /**
     * Devuelve los campos para la pestaña de códigos de barras.
     */
    private function getBarcodesFields($id): array
    {
        $html = '<div class="card shadow-sm border-0 mt-3 mb-4"><div class="card-body p-0">';

        if (empty($id)) {
            $html .= '<div class="alert alert-info m-3 mb-3">' . $this->transLegacy('no-data') . '</div></div></div>';
            return [new StaticText($html)];
        }

        // Buscamos todas las variantes del producto
        $variantes = \FacturaScripts\Plugins\Trading\Model\Variante::all([\FacturaScripts\Core\Where::eq('idproducto', $id)]);
        $variantIds = array_map(fn($v) => $v->idvariante, $variantes);

        if (empty($variantIds)) {
            $html .= '<div class="alert alert-info m-3 mb-3">' . $this->transLegacy('no-data') . '</div></div></div>';
            return [new StaticText($html)];
        }

        // Buscamos los códigos de barras de esas variantes
        $barcodes = \Modules\Barcodes\Model\ProductBarcode::all([\FacturaScripts\Core\Where::in('idvariante', $variantIds)]);
        
        if (empty($barcodes)) {
            $html .= '<div class="alert alert-info m-3 mb-3">' . $this->transLegacy('no-data') . '</div></div></div>';
            return [new StaticText($html)];
        }

        $html .= '<div class="table-responsive m-0"><table class="table table-sm table-striped table-hover mb-0">';
        $html .= '<thead class="table-light"><tr>';
        $html .= '<th class="px-3 py-2 text-muted">' . $this->transLegacy('variant') . '</th>';
        $html .= '<th class="px-3 py-2 text-muted">' . $this->transLegacy('barcode') . '</th>';
        $html .= '<th class="px-3 py-2 text-muted">' . $this->transLegacy('type') . '</th>';
        $html .= '<th class="px-3 py-2 text-muted">' . $this->transLegacy('quantity') . '</th>';
        $html .= '<th class="px-3 py-2 text-muted">' . $this->transLegacy('description') . '</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($barcodes as $barcode) {
            $variant = array_values(array_filter($variantes, fn($v) => $v->idvariante == $barcode->idvariante))[0] ?? null;
            $variantDesc = $variant ? $variant->description(true) : $barcode->idvariante;

            $html .= '<tr>';
            $html .= '<td class="px-3 py-2 align-middle">' . htmlspecialchars((string)$variantDesc) . '</td>';
            $html .= '<td class="px-3 py-2 align-middle">' . htmlspecialchars((string)$barcode->codbarras) . '</td>';
            $html .= '<td class="px-3 py-2 align-middle">' . htmlspecialchars((string)$barcode->tipo) . '</td>';
            $html .= '<td class="px-3 py-2 align-middle">' . htmlspecialchars((string)$barcode->cantidad) . '</td>';
            $html .= '<td class="px-3 py-2 align-middle">' . htmlspecialchars((string)$barcode->descripcion) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';
        $html .= '</div></div>';

        return [new StaticText($html)];
    }

    private function countBarcodes($id): int
    {
        if (empty($id)) {
            return 0;
        }

        $variantes = \FacturaScripts\Plugins\Trading\Model\Variante::all([\FacturaScripts\Core\Where::eq('idproducto', $id)]);
        $variantIds = array_map(fn($v) => $v->idvariante, $variantes);

        if (empty($variantIds)) {
            return 0;
        }

        return \Modules\Barcodes\Model\ProductBarcode::count([\FacturaScripts\Core\Where::in('idvariante', $variantIds)]);
    }
}
