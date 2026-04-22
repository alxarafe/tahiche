<?php

declare(strict_types=1);

namespace Tahiche\Test\Modern\Infrastructure\Adapter;

use PHPUnit\Framework\TestCase;
use Tahiche\Infrastructure\Adapter\TahicheRepository;
use FacturaScripts\Core\Model\Divisa;

class TahicheRepositoryTest extends TestCase
{
    private TahicheRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        // Usamos Divisa como modelo de prueba por ser simple y existir en el Core
        $this->repository = new TahicheRepository(Divisa::class);
    }

    public function testGetPrimaryKey(): void
    {
        $this->assertEquals('coddivisa', $this->repository->getPrimaryKey());
    }

    public function testNewRecordReturnsArray(): void
    {
        $record = $this->repository->newRecord();
        $this->assertIsArray($record);
        $this->assertArrayHasKey('coddivisa', $record);
    }

    public function testGetFieldMetadata(): void
    {
        $metadata = $this->repository->getFieldMetadata();
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('coddivisa', $metadata);
        $this->assertEquals('text', $metadata['coddivisa']['genericType']);
    }
}
