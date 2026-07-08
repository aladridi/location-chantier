<?php
namespace App\Entity\Enum;

enum RentalStatus: string
{
    case PENDING = 'pending';      // En attente
    case ACTIVE = 'active';        // Actif (loué)
    case OVERDUE = 'overdue';      // En retard
    case RETURNED = 'returned';    // Rendu
    case DAMAGED = 'damaged';      // Endommagé

    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'En attente',
            self::ACTIVE => 'En cours',
            self::OVERDUE => 'En retard',
            self::RETURNED => 'Rendu',
            self::DAMAGED => 'Endommagé',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::PENDING, self::ACTIVE, self::OVERDUE]);
    }

    public function canBeReturned(): bool
    {
        return in_array($this, [self::ACTIVE, self::OVERDUE]);
    }
}