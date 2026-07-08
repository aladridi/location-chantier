<?php
namespace App\Observer;

use App\Entity\Rental;

class RentalNotification
{
    public function onRentalCreated(array $data): void
    {
        $rental = $data['rental'] ?? null;
        if (!$rental instanceof Rental) {
            return;
        }

        // Simulation d'envoi d'email ou notification
        $message = sprintf(
            "📧 Nouvelle location créée :\n" .
            "Client: %s\n" .
            "Équipement: %s\n" .
            "Période: %s\n" .
            "Prix total: %.2f €\n" .
            "Statut: %s",
            $rental->getClient()->getDisplayName(),
            $rental->getEquipment()->getName(),
            $rental->getFormattedDateRange(),
            $rental->getTotalPrice(),
            $rental->status->getLabel()
        );

        error_log($message);
    }

    public function onRentalReturned(array $data): void
    {
        $rental = $data['rental'] ?? null;
        if (!$rental instanceof Rental) {
            return;
        }

        $message = sprintf(
            "📦 Location retournée :\n" .
            "ID: %d\n" .
            "Client: %s\n" .
            "Équipement: %s\n" .
            "Durée: %d jours\n" .
            "Pénalités: %.2f €",
            $rental->getId(),
            $rental->getClient()->getDisplayName(),
            $rental->getEquipment()->getName(),
            $rental->durationInDays,
            $rental->penaltyAmount
        );

        error_log($message);
    }
}