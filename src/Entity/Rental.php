<?php
namespace App\Entity;

use App\Entity\Enum\RentalStatus;
use App\Core\Validator\Constraints as Assert;

class Rental
{
    // Propriétés avec asymétrique visibility
    public private(set) ?int $id = null;
    public private(set) \DateTimeImmutable $createdAt;
    public private(set) \DateTimeImmutable $updatedAt;

    // Hook pour le statut avec transformation automatique

    public RentalStatus $status {
        get => $this->status;
        set (RentalStatus $status) {
            // Validation du changement de statut
            if (isset($this->status)) {
                $this->validateStatusTransition($this->status, $status);
            }
            $this->status = $status;
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    // Hook pour le prix total (calculé automatiquement)

    public float $totalPrice {
        get => $this->totalPrice;
        set (float $value) {
            if ($value < 0) {
                throw new \InvalidArgumentException('Le prix total ne peut pas être négatif');
            }
            $this->totalPrice = $value;
        }
    }

    // Hook pour les dates avec validation

    public \DateTimeImmutable $startDate {
        get => $this->startDate;
        set (\DateTimeImmutable $date) {
            // Ne peut pas être dans le passé (sauf si c'est une modification)
            if (!$this->id && $date < new \DateTimeImmutable()) {
                throw new \InvalidArgumentException('La date de début ne peut pas être dans le passé');
            }
            $this->startDate = $date;
            $this->updatedAt = new \DateTimeImmutable();
        }
    }


    public \DateTimeImmutable $endDate {
        get => $this->endDate;
        set (\DateTimeImmutable $date) {
            if ($date <= $this->startDate) {
                throw new \InvalidArgumentException('La date de fin doit être postérieure à la date de début');
            }
            $this->endDate = $date;
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    // Propriété calculée : durée en jours
    public int $durationInDays {
        get => $this->startDate->diff($this->endDate)->days;
    }

    // Propriété calculée : est en retard ?
    public bool $isOverdue {
        get => $this->status === RentalStatus::ACTIVE
            && new \DateTimeImmutable() > $this->endDate;
    }

    // Propriété calculée : jours de retard
    public int $overdueDays {
        get {
            if (!$this->isOverdue) {
                return 0;
            }
            return (new \DateTimeImmutable())->diff($this->endDate)->days;
        }
    }

    // Propriété calculée : montant des pénalités (10% par jour de retard)
    public float $penaltyAmount {
        get {
            if (!$this->isOverdue) {
                return 0.0;
            }
            return $this->totalPrice * 0.10 * $this->overdueDays;
        }
    }

    public function __construct(
        private Client $client,
        private Equipment $equipment,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        float $totalPrice,
        RentalStatus $status = RentalStatus::PENDING,
        private ?string $notes = null,
    ) {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->totalPrice = $totalPrice;
        $this->status = $status;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters/Setters simples
    public function getId(): ?int { return $this->id; }

    public function setId(int $id): self
    {
        if ($this->id !== null) {
            throw new \RuntimeException('L\'ID ne peut pas être modifié');
        }
        $this->id = $id;
        return $this;
    }

    public function getClient(): Client { return $this->client; }
    public function getEquipment(): Equipment { return $this->equipment; }
    public function getNotes(): ?string { return $this->notes; }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    // Méthodes métier - Gestion du cycle de vie

    public function confirm(): self
    {
        if ($this->status !== RentalStatus::PENDING) {
            throw new \RuntimeException('Seule une location en attente peut être confirmée');
        }

        // Vérifier la disponibilité de l'équipement
        if (!$this->equipment->isAvailable()) {
            throw new \RuntimeException('L\'équipement n\'est plus disponible');
        }

        $this->status = RentalStatus::ACTIVE;
        $this->equipment->markAsRented();
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function return(): self
    {
        if (!$this->status->canBeReturned()) {
            throw new \RuntimeException('Cette location ne peut pas être retournée');
        }

        $this->status = RentalStatus::RETURNED;
        $this->equipment->markAsAvailable();
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function markAsOverdue(): self
    {
        if ($this->status !== RentalStatus::ACTIVE) {
            throw new \RuntimeException('Seule une location active peut être marquée en retard');
        }

        $this->status = RentalStatus::OVERDUE;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function markAsDamaged(): self
    {
        if ($this->status !== RentalStatus::ACTIVE && $this->status !== RentalStatus::OVERDUE) {
            throw new \RuntimeException('Seule une location active ou en retard peut être marquée endommagée');
        }

        $this->status = RentalStatus::DAMAGED;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    // Validation des transitions de statut
    private function validateStatusTransition(RentalStatus $from, RentalStatus $to): void
    {
        $allowed = [
            RentalStatus::PENDING->value => [RentalStatus::ACTIVE, RentalStatus::CANCELLED],
            RentalStatus::ACTIVE->value => [RentalStatus::OVERDUE, RentalStatus::RETURNED, RentalStatus::DAMAGED],
            RentalStatus::OVERDUE->value => [RentalStatus::RETURNED, RentalStatus::DAMAGED],
            RentalStatus::DAMAGED->value => [], // Terminus
            RentalStatus::RETURNED->value => [], // Terminus
        ];

        if (!isset($allowed[$from->value]) || !in_array($to, $allowed[$from->value], true)) {
            throw new \RuntimeException(
                sprintf('Transition de statut invalide : %s -> %s', $from->value, $to->value)
            );
        }
    }

    // Méthodes pour les calculs
    public function getTotalWithPenalties(): float
    {
        return $this->totalPrice + $this->penaltyAmount;
    }

    public function getFormattedDateRange(): string
    {
        return sprintf(
            'Du %s au %s (%d jours)',
            $this->startDate->format('d/m/Y'),
            $this->endDate->format('d/m/Y'),
            $this->durationInDays
        );
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    // Représentation
    public function __toString(): string
    {
        return sprintf(
            'Location #%d - %s - %s (%s)',
            $this->id ?? 0,
            $this->client->getDisplayName(),
            $this->equipment->getName(),
            $this->status->getLabel()
        );
    }

    // Sérialisation
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'client' => $this->client->toArray(),
            'equipment' => $this->equipment->toArray(),
            'start_date' => $this->startDate->format('Y-m-d H:i:s'),
            'end_date' => $this->endDate->format('Y-m-d H:i:s'),
            'duration_in_days' => $this->durationInDays,
            'total_price' => $this->totalPrice,
            'penalty_amount' => $this->penaltyAmount,
            'total_with_penalties' => $this->getTotalWithPenalties(),
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'is_overdue' => $this->isOverdue,
            'overdue_days' => $this->overdueDays,
            'is_active' => $this->isActive(),
            'notes' => $this->notes,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}