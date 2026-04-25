<?php

declare(strict_types=1);

namespace Tahiche\Infrastructure\Component\Field;

class Money extends Number
{
    protected string $component = 'money';
    protected string $currency = 'EUR';

    public function __construct(string $field, string $label, string $currency = 'EUR', array $options = [])
    {
        parent::__construct($field, $label, $options);
        $this->currency = $currency;
    }

    public function getType(): string
    {
        return 'money';
    }

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        $data['options']['currency'] = $this->currency;
        $data['options']['step'] = $data['options']['step'] ?? '0.01';
        return $data;
    }
}
