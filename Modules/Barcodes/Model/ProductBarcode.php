<?php

declare(strict_types=1);

namespace Modules\Barcodes\Model;

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Tools;

class ProductBarcode extends ModelClass
{
    use ModelTrait;

    public ?int $id = null;
    public int $idproducto = 0;
    public string $codbarras = '';
    public string $tipo = 'EAN-13';
    public float $cantidad = 1;
    public ?string $descripcion = null;
    public ?string $fechaalta = null;

    public function clear()
    {
        parent::clear();
        $this->fechaalta = date('Y-m-d H:i:s');
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'productos_codbarras';
    }

    public static function findByBarcode(string $code): ?array
    {
        $barcode = new self();
        if ($barcode->loadFromCode('', [new \FacturaScripts\Core\Where('codbarras', '=', $code)])) {
            $product = new \FacturaScripts\Core\Model\Producto();
            if ($product->loadFromCode('', [new \FacturaScripts\Core\Where('idproducto', '=', $barcode->idproducto)])) {
                return [
                    'producto' => $product,
                    'cantidad' => $barcode->cantidad,
                    'barcode' => $barcode,
                ];
            }
        }
        return null;
    }

    public function test(): bool
    {
        $this->codbarras = trim($this->codbarras);
        if (empty($this->codbarras)) {
            Tools::log()->warning('barcode-empty');
            return false;
        }

        if ($this->cantidad <= 0) {
            $this->cantidad = 1;
        }

        if (empty($this->fechaalta)) {
            $this->fechaalta = date('Y-m-d H:i:s');
        }

        return parent::test();
    }

    public static function barcodeTypes(): array
    {
        return [
            'EAN-13' => 'EAN-13 (International)',
            'EAN-8' => 'EAN-8 (Compacto)',
            'UPC-A' => 'UPC-A (Norte América)',
            'UPC-E' => 'UPC-E (Compacto NA)',
            'Code-128' => 'Code 128 (Alfanumérico)',
            'Code-39' => 'Code 39',
            'ITF-14' => 'ITF-14 (Cajas/Pallets)',
            'GS1-128' => 'GS1-128 (Logística)',
            'QR' => 'QR Code',
            'DataMatrix' => 'Data Matrix',
        ];
    }
}
