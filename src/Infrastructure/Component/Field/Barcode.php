<?php

declare(strict_types=1);

namespace Tahiche\Infrastructure\Component\Field;

use Alxarafe\ResourceController\Component\AbstractField;

class Barcode extends AbstractField
{
    protected string $component = 'barcode';
    protected string $type;

    public function __construct(string $field, string $label, string $type = 'EAN-13', array $options = [])
    {
        parent::__construct($field, $label, $options);
        $this->type = $type;
    }

    public function getType(): string
    {
        return 'barcode';
    }

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        $data['options']['barcodeType'] = $this->type;
        return $data;
    }
}
