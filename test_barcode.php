<?php
require 'vendor/autoload.php';
define('FS_FOLDER', __DIR__);
require 'config.php';
\FacturaScripts\Core\Kernel::init();

$db = \FacturaScripts\Core\Base\DataBase::db();

// Find the product "PELOTA"
$product = $db->select("SELECT idproducto FROM productos WHERE referencia = 'PELOTA' OR descripcion LIKE '%PELOTA%' LIMIT 1");

if (empty($product)) {
    echo "Product PELOTA not found.\n";
    exit(1);
}

$id = $product[0]['idproducto'];
echo "Found PELOTA with ID: $id\n";

// Ensure the barcodes table exists
// Try inserting a barcode
try {
    $db->exec("INSERT INTO product_barcodes (idproducto, barcode, creation_date) VALUES ($id, '8412345678901', NOW())");
    $db->exec("INSERT INTO product_barcodes (idproducto, barcode, creation_date) VALUES ($id, '8412345678902', NOW())");
    echo "Inserted 2 barcodes for PELOTA.\n";
} catch (\Exception $e) {
    echo "Error inserting barcodes: " . $e->getMessage() . "\n";
}
