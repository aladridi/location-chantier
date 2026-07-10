<?php
namespace App\Entity\Enum;

enum RentalStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case OVERDUE = 'overdue';
    case RETURNED = 'returned';
    case DAMAGED = 'damaged';

    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'En attente',
            self::ACTIVE => 'En cours',
            self::OVERDUE => 'En retard',
            self::RETURNED => 'Retournée',
            self::DAMAGED => 'Endommagée',
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