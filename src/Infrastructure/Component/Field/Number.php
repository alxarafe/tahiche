<?php

declare(strict_types=1);

namespace Tahiche\Infrastructure\Component\Field;

use Alxarafe\ResourceController\Component\AbstractField;

class Number extends AbstractField
{
    protected string $component = 'number';

    public function __construct(string $field, string $label, array $options = [])
    {
        parent::__construct($field, $label, $options);
    }

    public function getType(): string
    {
        return 'number';
    }
}
