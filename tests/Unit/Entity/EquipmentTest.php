<?php

namespace Tests\Unit\Entity;

use App\Entity\Equipment;
use App\Entity\Category;
use PHPUnit\Framework\TestCase;
use App\Attribute\Entity;
use App\Attribute\Table;
use App\Attribute\Column;
use App\Attribute\Id;
use App\Attribute\Relation;
class EquipmentTest extends TestCase
{
    private function createCategory(
        string $name,
        string $slug,
        float $multiplier = 1.0,
        bool $requiresMaintenance = false
    ): Category {
        return new Category(
            name: $name,
            slug: $slug,
            dailyRateMultiplier: $multiplier,
            requiresMaintenance: $requiresMaintenance
        );
    }

    public function testEquipmentCreation(): void
    {
        $category = $this->createCategory('Excavatrice', 'excavator');

        $equipment = new Equipment(
            'Pelle mecanique',
            $category,
            150.00
        );

        $this->assertSame('Pelle mecanique', $equipment->getName());
        $this->assertSame($category, $equipment->getCategory());
        $this->assertEquals(150.00, $equipment->getDailyRate());
        $this->assertTrue($equipment->isAvailable());
        $this->assertSame('disponible', $equipment->getStatus());
        $this->assertNotNull($equipment->getLastMaintenance());
    }

    public function testEquipmentNameValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le nom doit faire au moins 3 caractères'
        );

        $category = $this->createCategory('Excavatrice', 'excavator');

        new Equipment('AB', $category, 150.00);
    }

    public function testEquipmentDailyRateValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le taux journalier ne peut pas être négatif'
        );

        $category = $this->createCategory('Excavatrice', 'excavator');

        new Equipment('Pelle', $category, -150.00);
    }

    public function testEquipmentMarkAsRented(): void
    {
        $category = $this->createCategory('Excavatrice', 'excavator');
        $equipment = new Equipment('Pelle', $category, 150.00);

        $equipment->markAsRented();

        $this->assertFalse($equipment->isAvailable());
        $this->assertSame('loué', $equipment->getStatus());
    }

    public function testEquipmentNeedsMaintenance(): void
    {
        $category = $this->createCategory(
            'Grue',
            'crane',
            1.5,
            true
        );

        $equipment = new Equipment(
            'Grue',
            $category,
            200.00,
            lastMaintenance: new \DateTimeImmutable('-100 days')
        );

        $this->assertTrue($equipment->needsMaintenance());
        $this->assertNotNull($equipment->getMaintenanceAlert());
    }

    public function testEquipmentEffectiveDailyRate(): void
    {
        $category = $this->createCategory('Grue', 'crane', 1.5, true);
        $equipment = new Equipment('Grue', $category, 200.00);

        $this->assertEquals(300.00, $equipment->getEffectiveDailyRate());
    }
}

