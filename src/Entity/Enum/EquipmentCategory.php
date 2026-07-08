<?php
namespace App\Entity\Enum;

enum EquipmentCategory: string
{
    case BULLDOZER = 'bulldozer';
    case CRANE = 'crane';
    case EXCAVATOR = 'excavator';
    case LOADER = 'loader';
    case DUMP_TRUCK = 'dump_truck';
    case COMPRESSOR = 'compressor';
    case GENERATOR = 'generator';
    case SCAFFOLDING = 'scaffolding';
    case CONCRETE_MIXER = 'concrete_mixer';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match($this) {
            self::BULLDOZER => 'Bulldozer',
            self::CRANE => 'Grue',
            self::EXCAVATOR => 'Excavatrice',
            self::LOADER => 'Chargeuse',
            self::DUMP_TRUCK => 'Camion-benne',
            self::COMPRESSOR => 'Compresseur',
            self::GENERATOR => 'Générateur',
            self::SCAFFOLDING => 'Échafaudage',
            self::CONCRETE_MIXER => 'Bétonnière',
            self::OTHER => 'Autre',
        };
    }

    public function getDailyRateMultiplier(): float
    {
        return match($this) {
            self::CRANE, self::BULLDOZER => 1.5, // Matériel lourd
            self::COMPRESSOR, self::GENERATOR => 1.2,
            self::SCAFFOLDING => 0.8,
            default => 1.0,
        };
    }

    public function requiresMaintenanceCheck(): bool
    {
        return in_array($this, [
            self::CRANE,
            self::BULLDOZER,
            self::EXCAVATOR,
            self::LOADER,
        ]);
    }
}