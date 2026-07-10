<?php
namespace App\Entity;

use App\Entity\Enum\RentalStatus;

class Rental
{
    public private(set) ?int $id = null;
    public private(set) \DateTimeImmutable $createdAt;
    public private(set) \DateTimeImmutable $updatedAt;

    private Client $client;
    private Equipment $equipment;
    private \DateTimeImmutable $startDate;
    private \DateTimeImmutable $endDate;
    private float $totalPrice;
    private RentalStatus $status;
    private float $penaltyAmount = 0;
    private ?\DateTimeImmutable $returnedAt = null;
    private ?string $notes = null;

    public function __construct(
        Client $client,
        Equipment $equipment,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        float $totalPrice,
        RentalStatus $status = RentalStatus::PENDING,
        ?string $notes = null
    ) {
        $this->client = $client;
        $this->equipment = $equipment;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->totalPrice = $totalPrice;
        $this->status = $status;
        $this->notes = $notes;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getClient(): Client { return $this->client; }
    public function getEquipment(): Equipment { return $this->equipment; }
    public function getStartDate(): \DateTimeImmutable { return $this->startDate; }
    public function getEndDate(): \DateTimeImmutable { return $this->endDate; }
    public function getTotalPrice(): float { return $this->totalPrice; }
    public function getStatus(): RentalStatus { return $this->status; }
    public function getPenaltyAmount(): float { return $this->penaltyAmount; }
    public function getReturnedAt(): ?\DateTimeImmutable { return $this->returnedAt; }
    public function getNotes(): ?string { return $this->notes; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    public function getStatusLabel(): string
    {
        return $this->status->getLabel();
    }

    public function getDurationInDays(): int
    {
        return $this->startDate->diff($this->endDate)->days;
    }

    public function getFormattedDateRange(): string
    {
        return sprintf(
            'Du %s au %s (%d jours)',
            $this->startDate->format('d/m/Y'),
            $this->endDate->format('d/m/Y'),
            $this->getDurationInDays()
        );
    }

    public function isActive(): bool
    {
        return in_array($this->status, [RentalStatus::PENDING, RentalStatus::ACTIVE, RentalStatus::OVERDUE]);
    }

    public function isReturned(): bool
    {
        return $this->status === RentalStatus::RETURNED;
    }

    public function isOverdue(): bool
    {
        return $this->status === RentalStatus::OVERDUE;
    }

    // Méthodes métier
    public function confirm(): self
    {
        if ($this->status !== RentalStatus::PENDING) {
            throw new \RuntimeException('Seule une location en attente peut être confirmée');
        }

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
        if ($this->isReturned()) {
            throw new \RuntimeException('Cette location a déjà été retournée');
        }

        $this->status = RentalStatus::RETURNED;
        $this->returnedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();

        // Calculer les pénalités si en retard
        if ($this->isOverdue()) {
            $days = (new \DateTimeImmutable())->diff($this->endDate)->days;
            $this->penaltyAmount = $this->totalPrice * 0.10 * $days;
        }

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
        if (!in_array($this->status, [RentalStatus::ACTIVE, RentalStatus::OVERDUE])) {
            throw new \RuntimeException('Seule une location active ou en retard peut être marquée endommagée');
        }

        $this->status = RentalStatus::DAMAGED;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getPricingBreakdown(): ?array
    {
        return [
            'base_price' => $this->totalPrice,
            'penalty' => $this->penaltyAmount,
            'total' => $this->totalPrice + $this->penaltyAmount,
        ];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'client' => $this->client->toArray(),
            'client_name' => $this->client->getDisplayName(),
            'equipment' => $this->equipment->toArray(),
            'equipment_name' => $this->equipment->getName(),
            'start_date' => $this->startDate->format('Y-m-d H:i:s'),
            'end_date' => $this->endDate->format('Y-m-d H:i:s'),
            'duration_in_days' => $this->getDurationInDays(),
            'total_price' => $this->totalPrice,
            'penalty_amount' => $this->penaltyAmount,
            'total_with_penalties' => $this->totalPrice + $this->penaltyAmount,
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'is_active' => $this->isActive(),
            'is_returned' => $this->isReturned(),
            'is_overdue' => $this->isOverdue(),
            'returned_at' => $this->returnedAt?->format('Y-m-d H:i:s'),
            'notes' => $this->notes,
            'date_range' => $this->getFormattedDateRange(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}