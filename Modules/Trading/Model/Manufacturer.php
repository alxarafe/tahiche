<?php

declare(strict_types=1);

namespace Modules\Trading\Model;

use FacturaScripts\Core\Model\Fabricante;

/**
 * Modelo de Fabricante (Manufacturer).
 * Extiende temporalmente del modelo Legacy para mantener la lógica de negocio intacta
 * (validaciones en test(), generación de códigos en saveInsert(), etc.)
 * hasta que se complete el estrangulamiento.
 */
class Manufacturer extends Fabricante
{
{
    // Aquí podemos añadir métodos modernos que el ResourceController necesite
    // sin ensuciar el modelo Legacy.
}
