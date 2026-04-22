<?php

/*
 * Copyright (C) 2024-2026 Rafael San José <rsanjose@alxarafe.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Tahiche\Infrastructure\Component\Container;

use Alxarafe\ResourceController\Component\Container\AbstractContainer;

/**
 * DetailLines component for master-detail editable grids (e.g. document lines).
 * Implemented locally in Tahiche to extend Alxarafe's capabilities without modifying the core vendor.
 */
class DetailLines extends AbstractContainer
{
    protected string $containerTemplate = 'detail_lines';
    protected string $component = 'detail_lines';

    protected string $modelClass;
    protected string $foreignKey;
    protected bool $sortable;
    protected bool $addRow;
    protected bool $removeRow;
    protected bool $autoRecalculate;
    protected array $footerTotals;
    protected array $lineColumns;

    /**
     * @param string $field The field/container name
     * @param string $label Translation key for the header
     * @param array $columns Array of AbstractField representing the editable columns
     * @param array $options Configuration options (model, foreignKey, sortable, etc.)
     */
    public function __construct(
        string $field,
        string $label,
        array $columns = [],
        array $options = []
    ) {
        $this->lineColumns = $columns;
        $this->modelClass = $options['model'] ?? '';
        $this->foreignKey = $options['foreignKey'] ?? '';
        $this->sortable = $options['sortable'] ?? false;
        $this->addRow = $options['addRow'] ?? true;
        $this->removeRow = $options['removeRow'] ?? true;
        $this->autoRecalculate = $options['autoRecalculate'] ?? false;
        $this->footerTotals = $options['footerTotals'] ?? [];

        // Pass everything to the base field options so it's serialized to the view if needed
        $options['columns'] = $columns;
        
        // The children parameter is empty because columns define the grid schema, not direct children
        parent::__construct($field, $label, [], $options);
    }

    public function getLineColumns(): array { return $this->lineColumns; }
    public function getModelClass(): string { return $this->modelClass; }
    public function getForeignKey(): string { return $this->foreignKey; }
    public function isSortable(): bool { return $this->sortable; }
    public function canAddRow(): bool { return $this->addRow; }
    public function canRemoveRow(): bool { return $this->removeRow; }
    public function hasAutoRecalculate(): bool { return $this->autoRecalculate; }
    public function getFooterTotals(): array { return $this->footerTotals; }

    #[\Override]
    public function getContainerType(): string
    {
        return 'detail_lines';
    }

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        $data['options']['columns'] = $this->lineColumns;
        $data['options']['footerTotals'] = $this->footerTotals;
        $data['options']['sortable'] = $this->sortable;
        $data['options']['addRow'] = $this->addRow;
        $data['options']['removeRow'] = $this->removeRow;
        return $data;
    }
}
