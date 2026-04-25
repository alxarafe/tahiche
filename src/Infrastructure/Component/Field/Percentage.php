<?php

declare(strict_types=1);

namespace Tahiche\Infrastructure\Component\Field;

class Percentage extends Number
{
    protected string $component = 'percentage';

    public function getType(): string
    {
        return 'percentage';
    }

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        $data['options']['min'] = $data['options']['min'] ?? 0;
        $data['options']['max'] = $data['options']['max'] ?? 100;
        $data['options']['step'] = $data['options']['step'] ?? '0.01';
        return $data;
    }
}
