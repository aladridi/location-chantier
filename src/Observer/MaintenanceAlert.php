<?php
namespace App\Observer;

use App\Entity\Rental;

class MaintenanceAlert
{
    public function onRentalCreated(array $data): void
    {
        $rental = $data['rental'] ?? null;
        if (!$rental instanceof Rental) {
            return;
        }

        $equipment = $rental->getEquipment();

        // Vérifier si l'équipement nécessite une maintenance
        if ($equipment->needsMaintenance()) {
            $message = sprintf(
                "⚠️ ALERTE MAINTENANCE !\n" .
                "L'équipement '%s' (ID: %d) a été loué mais nécessite une maintenance.\n" .
                "Dernière maintenance: %s\n" .
                "Catégorie: %s\n" .
                "Location ID: %d",
                $equipment->getName(),
                $equipment->getId(),
                $equipment->getLastMaintenance()?->format('d/m/Y') ?? 'Jamais',
                $equipment->getCategory()->getLabel(),
                $rental->getId()
            );

            error_log($message);
        }

        // Vérifier si la location est très longue
        $days = $rental->durationInDays;
        if ($days > 30) {
            $message = sprintf(
                "📢 Location longue durée détectée :\n" .
                "Équipement: %s\n" .
                "Durée: %d jours\n" .
                "Client: %s\n" .
                "Recommandation: Planifier une vérification à mi-parcours.",
                $equipment->getName(),
                $days,
                $rental->getClient()->getDisplayName()
            );

            error_log($message);
        }
    }

    public function onRentalOverdue(array $data): void
    {
        $rental = $data['rental'] ?? null;
        if (!$rental instanceof Rental) {
            return;
        }

        $message = sprintf(
            "⚠️ ALERTE RETARD DE LOCATION !\n" .
            "Location ID: %d\n" .
            "Client: %s\n" .
            "Équipement: %s\n" .
            "Retard: %d jours\n" .
            "Pénalités estimées: %.2f €\n" .
            "Action requise: Contacter le client immédiatement.",
            $rental->getId(),
            $rental->getClient()->getDisplayName(),
            $rental->getEquipment()->getName(),
            $rental->overdueDays,
            $rental->penaltyAmount
        );

        error_log($message);
    }
}