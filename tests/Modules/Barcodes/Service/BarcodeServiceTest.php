<?php

declare(strict_types=1);

namespace Tahiche\Tests\Modules\Barcodes\Service;

use PHPUnit\Framework\TestCase;
use Modules\Barcodes\Service\BarcodeService;

class BarcodeServiceTest extends TestCase
{
    private BarcodeService $service;

    protected function setUp(): void
    {
        $this->service = new BarcodeService();
    }

    public function testValidateEan13ReturnsTrueForValidCode(): void
    {
        // 5449000000996 is a valid Coca-Cola EAN-13
        $this->assertTrue($this->service->validateEan13('5449000000996'));
    }

    public function testValidateEan13ReturnsFalseForInvalidCode(): void
    {
        // Changed last digit from 6 to 5
        $this->assertFalse($this->service->validateEan13('5449000000995'));
    }

    public function testValidateEan13ReturnsFalseForWrongLength(): void
    {
        $this->assertFalse($this->service->validateEan13('544900000099')); // 12 chars
        $this->assertFalse($this->service->validateEan13('54490000009961')); // 14 chars
    }

    public function testValidateEan13ReturnsFalseForNonNumeric(): void
    {
        $this->assertFalse($this->service->validateEan13('544900000099A'));
    }
}
