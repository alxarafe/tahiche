<?php

declare(strict_types=1);

namespace Tahiche\Infrastructure\Adapter;

use Alxarafe\ResourceController\Contracts\QueryContract;
use Alxarafe\ResourceController\Result\PaginatedResult;
use Tahiche\Infrastructure\Base\DataBaseWhere;

/**
 * TahicheQuery — Implements QueryContract for Tahiche.
 */
class TahicheQuery implements QueryContract
{
    /** @var DataBaseWhere[] */
    private array $wheres = [];

    /** @var array<string, string> e.g. ['nombre' => 'ASC'] */
    private array $orders = [];

    /** @var string[] */
    private array $searchFields = [];

    private string $searchTerm = '';

    /**
     * @param string $modelClass Fully qualified FS ModelClass name
     */
    public function __construct(
        private readonly string $modelClass,
    ) {
    }

    public function where(string $field, string $operator, mixed $value): static
    {
        $this->wheres[] = new DataBaseWhere($field, $value, $operator);
        return $this;
    }

    public function whereNull(string $field): static
    {
        $this->wheres[] = new DataBaseWhere($field, null, 'IS');
        return $this;
    }

    public function whereNotNull(string $field): static
    {
        $this->wheres[] = new DataBaseWhere($field, null, 'IS NOT');
        return $this;
    }

    public function whereIn(string $field, array $values): static
    {
        if (!empty($values)) {
            $this->wheres[] = new DataBaseWhere($field, implode(',', $values), 'IN');
        }
        return $this;
    }

    public function whereNotIn(string $field, array $values): static
    {
        if (!empty($values)) {
            $this->wheres[] = new DataBaseWhere($field, implode(',', $values), 'NOT IN');
        }
        return $this;
    }

    public function search(array $fields, string $term): static
    {
        if (!empty($term)) {
            $this->searchFields = $fields;
            $this->searchTerm = $term;
            // FS uses pipe-separated field names for multi-field LIKE search
            $fieldStr = implode('|', $fields);
            $this->wheres[] = new DataBaseWhere($fieldStr, $term, 'LIKE');
        }
        return $this;
    }

    public function with(array $relations): static
    {
        // FS ModelClass doesn't support eager loading — no-op
        return $this;
    }

    public function orderBy(string $field, string $direction = 'ASC'): static
    {
        $this->orders[$field] = strtoupper($direction);
        return $this;
    }

    public function paginate(int $limit, int $offset = 0): PaginatedResult
    {
        $modelClass = $this->modelClass;

        /** @var \FacturaScripts\Core\Model\Base\ModelClass[] $items */
        $items = $modelClass::all($this->wheres, $this->orders, $offset, $limit);

        $total = $this->count();

        $rows = array_map(fn($model) => $model->toArray(), $items);

        return new PaginatedResult($rows, $total, $limit, $offset);
    }

    public function count(): int
    {
        $modelClass = $this->modelClass;
        $model = new $modelClass();
        return $model->count($this->wheres);
    }

    public function whereGroup(callable $callback): static
    {
        // FS DataBaseWhere doesn't natively support grouped conditions,
        // but we can approximate by collecting wheres from a sub-query
        $subQuery = new self($this->modelClass);
        $callback($subQuery);
        // Merge sub-query wheres into main (flat merge — best effort)
        foreach ($subQuery->wheres as $where) {
            $this->wheres[] = $where;
        }
        return $this;
    }
}
