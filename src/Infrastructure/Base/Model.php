<?php

declare(strict_types=1);

namespace Tahiche\Infrastructure\Base;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Model\Base\ModelClass as FSModelClass;
use FacturaScripts\Core\Template\ExtensionsTrait;

/**
 * Clase base para los modelos de la nueva arquitectura.
 * Estrangula a ModelClass para que los nuevos modelos no tengan
 * dependencia directa con el Core original.
 */
#[\AllowDynamicProperties]
abstract class Model extends FSModelClass
{
    use ExtensionsTrait;

    /** @var array Cache de campos por clase para evitar colisiones entre modelos */
    protected static $fieldsByClass = [];

    /**
     * Devuelve el nombre de la tabla.
     * Sobreescribir en los modelos hijos.
     */
    abstract public static function tableName(): string;

    /**
     * Devuelve el nombre de la columna clave primaria.
     */
    abstract public static function primaryColumn(): string;

    /**
     * Returns the list of fields in the table.
     */
    public function getModelFields(): array
    {
        $class = get_class($this);
        if (empty(self::$fieldsByClass[$class])) {
            $db = new DataBase();
            $this->loadModelFields($db, static::tableName());
        }

        return self::$fieldsByClass[$class];
    }

    /**
     * Returns the name of the class of the model.
     */
    public function modelClassName(): string
    {
        $result = explode('\\', get_class($this));
        return end($result);
    }

    /**
     * Returns the full name of the model class.
     */
    protected function modelName(): string
    {
        return get_class($this);
    }

    /**
     * Implementación del método db() (opcional, para compatibilidad).
     */
    protected static function db(): DataBase
    {
        return new DataBase();
    }

    protected function loadModelFields(DataBase &$db, string $tableName): void
    {
        $class = get_class($this);
        if (isset(self::$fieldsByClass[$class]) && self::$fieldsByClass[$class]) {
            return;
        }

        // read from the cache
        $key = 'model-fields-' . $this->modelClassName();
        $fields = Cache::get($key);
        if (is_array($fields) && $fields) {
            self::$fieldsByClass[$class] = $fields;
            return;
        }

        if (false === $db->tableExists($tableName)) {
            self::$fieldsByClass[$class] = [];
            return;
        }

        // get from the database and store on the cache
        $fields = $db->getColumns($tableName);
        self::$fieldsByClass[$class] = $fields;
        Cache::set($key, $fields);
    }
}
