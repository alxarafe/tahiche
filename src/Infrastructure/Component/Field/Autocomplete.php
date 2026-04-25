<?php

declare(strict_types=1);

namespace Tahiche\Infrastructure\Component\Field;

use Alxarafe\ResourceController\Component\AbstractField;

class Autocomplete extends AbstractField
{
    protected string $component = 'autocomplete';
    protected string $modelClass;
    protected string $searchColumn;
    protected string $displayColumn;

    public function __construct(
        string $field,
        string $label,
        string $modelClass,
        string $searchColumn = 'nombre',
        string $displayColumn = 'nombre',
        array $options = []
    ) {
        parent::__construct($field, $label, $options);
        $this->modelClass = $modelClass;
        $this->searchColumn = $searchColumn;
        $this->displayColumn = $displayColumn;
    }

    public function getType(): string
    {
        return 'autocomplete';
    }

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        $data['options']['model'] = $this->modelClass;
        $data['options']['searchColumn'] = $this->searchColumn;
        $data['options']['displayColumn'] = $this->displayColumn;
        return $data;
    }
}
