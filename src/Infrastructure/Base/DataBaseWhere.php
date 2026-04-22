<?php

declare(strict_types=1);

namespace Tahiche\Infrastructure\Base;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere as FSDataBaseWhere;

/**
 * Versión estrangulada de DataBaseWhere.
 * Por ahora delega en la original de FacturaScripts, pero nos permite
 * añadir lógica propia o cambiar la implementación sin tocar los controladores.
 */
class DataBaseWhere extends FSDataBaseWhere
{
    // Aquí podemos añadir métodos personalizados o sobreescribir lógica
}
