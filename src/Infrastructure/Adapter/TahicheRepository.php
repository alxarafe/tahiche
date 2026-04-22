<?php

declare(strict_types=1);

namespace Tahiche\Infrastructure\Adapter;

use Alxarafe\ResourceController\Contracts\QueryContract;
use Alxarafe\ResourceController\Contracts\RepositoryContract;
use RuntimeException;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Model\Base\ModelClass;

/**
 * TahicheRepository — Implements RepositoryContract for Tahiche.
 */
class TahicheRepository implements RepositoryContract
{
    private string $modelClass;
    private ?string $primaryKey = null;

    /**
     * @param string $modelClass Fully qualified FS ModelClass name
     */
    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
        $this->primaryKey = $modelClass::primaryColumn();
    }

    private function log(string $message): void
    {
        $file = FS_FOLDER . '/MyFiles/debug.log';
        $time = date('Y-m-d H:i:s');
        file_put_contents($file, "[$time] $message\n", FILE_APPEND);
    }

    public function query(): QueryContract
    {
        return new TahicheQuery($this->modelClass);
    }

    public function find(string|int $id): ?array
    {
        $model = new $this->modelClass();
        $result = $model->get($id);
        if ($result === false) {
            return null;
        }
        return $result->toArray();
    }

    public function newRecord(): array
    {
        $model = new $this->modelClass();
        return $model->toArray();
    }

    public function save(string|int|null $id, array $data): array
    {
        $this->log('Saving record with data: ' . json_encode($data));

        $model = new $this->modelClass();

        // Load existing record for updates
        if ($id !== null && $id !== 'new' && $id !== '') {
            $this->log('Loading existing record: ' . $id);
            if (!$model->loadFromCode($id)) {
                $this->log('Record not found: ' . $id);
                throw new RuntimeException("Record not found: {$id}");
            }
        }

        // Apply submitted data
        $model->loadFromData($data);
        $this->log('Model loaded. Name: ' . ($model->nombre ?? 'N/A'));

        if (!$model->save()) {
            $this->log('Model save() returned false');
            throw new RuntimeException('Failed to save record');
        }

        return $model->toArray();
    }

    public function delete(string|int $id): bool
    {
        $model = new $this->modelClass();
        if (!$model->loadFromCode($id)) {
            return false;
        }
        return $model->delete();
    }

    public function getPrimaryKey(): string
    {
        $modelClass = $this->modelClass;
        return $modelClass::primaryColumn();
    }

    public function getFieldMetadata(): array
    {
        $model = new $this->modelClass();
        $fields = $model->getModelFields();
        $metadata = [];

        foreach ($fields as $name => $info) {
            $dbType = strtolower($info['type'] ?? 'varchar');

            // Strip length from type: varchar(255) → varchar
            $baseType = preg_replace('/\(.*\)/', '', $dbType) ?? $dbType;

            $genericType = match ($baseType) {
                'tinyint', 'boolean', 'bool' => 'boolean',
                'int', 'integer', 'serial', 'bigint', 'smallint', 'mediumint' => 'integer',
                'decimal', 'float', 'double', 'double precision', 'numeric', 'real' => 'decimal',
                'date' => 'date',
                'datetime', 'timestamp' => 'datetime',
                'time' => 'time',
                'text', 'mediumtext', 'longtext' => 'textarea',
                default => 'text',
            };

            $metadata[$name] = [
                'field' => $name,
                'label' => $name,
                'genericType' => $genericType,
                'dbType' => $dbType,
                'required' => ($info['is_nullable'] ?? 'YES') === 'NO',
                'nullable' => ($info['is_nullable'] ?? 'YES') === 'YES',
                'default' => $info['default'] ?? null,
            ];
        }

        return $metadata;
    }

    public function storageExists(): bool
    {
        $modelClass = $this->modelClass;
        $db = new DataBase();
        return $db->tableExists($modelClass::tableName());
    }
}
