<?php
namespace Tests\Unit\Entity;

use App\Entity\Equipment;
use App\Entity\Enum\EquipmentCategory;
use PHPUnit\Framework\TestCase;

class EquipmentTest extends TestCase
{
    public function testEquipmentCreation(): void
    {
        $equipment = new Equipment(
            'Pelle mécanique',
            EquipmentCategory::EXCAVATOR,
            150.00
        );

        $this->assertEquals('Pelle mécanique', $equipment->name);
        $this->assertEquals(EquipmentCategory::EXCAVATOR, $equipment->getCategory());
        $this->assertEquals(150.00, $equipment->dailyRate);
        $this->assertTrue($equipment->isAvailable());
        $this->assertEquals('disponible', $equipment->status);
        $this->assertNotNull($equipment->getLastMaintenance());
    }

    public function testEquipmentNameValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom doit faire au moins 3 caractères');

        new Equipment('AB', EquipmentCategory::EXCAVATOR, 150.00);
    }

    public function testEquipmentDailyRateValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le taux journalier ne peut pas être négatif');

        new Equipment('Pelle', EquipmentCategory::EXCAVATOR, -150.00);
    }

    public function testEquipmentMarkAsRented(): void
    {
        $equipment = new Equipment('Pelle', EquipmentCategory::EXCAVATOR, 150.00);

        $equipment->markAsRented();
        $this->assertFalse($equipment->isAvailable());
        $this->assertEquals('loué', $equipment->status);
    }

    public function testEquipmentNeedsMaintenance(): void
    {
        $equipment = new Equipment(
            'Grue',
            EquipmentCategory::CRANE,
            200.00,
            lastMaintenance: new \DateTimeImmutable('-100 days')
        );

        $this->assertTrue($equipment->needsMaintenance());
        $this->assertNotNull($equipment->getMaintenanceAlert());
    }

    public function testEquipmentEffectiveDailyRate(): void
    {
        $equipment = new Equipment('Grue', EquipmentCategory::CRANE, 200.00);

        // Crane a un multiplicateur de 1.5
        $this->assertEquals(300.00, $equipment->getEffectiveDailyRate());
    }
}