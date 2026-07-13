<?php
namespace App\Entity;

use App\Entity\Category;

class Equipment
{
    private ?int $id = null;
    private string $name;
    private Category $category;
    private float $dailyRate;
    private bool $available = true;
    private ?\DateTimeImmutable $lastMaintenance = null;
    private ?string $serialNumber = null;
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $name,
        Category $category,
        float $dailyRate,
        bool $available = true,
        ?\DateTimeImmutable $lastMaintenance = null,
        ?string $serialNumber = null,
    ) {
        $this->setName($name);
        $this->category = $category;
        $this->setDailyRate($dailyRate);
        $this->available = $available;
        $this->lastMaintenance = $lastMaintenance ?? new \DateTimeImmutable();
        $this->serialNumber = $serialNumber ? strtoupper(trim($serialNumber)) : null;
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getCategory(): Category { return $this->category; }
    public function getDailyRate(): float { return $this->dailyRate; }
    public function isAvailable(): bool { return $this->available; }
    public function getLastMaintenance(): ?\DateTimeImmutable { return $this->lastMaintenance; }
    public function getSerialNumber(): ?string { return $this->serialNumber; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getStatus(): string { return $this->available ? 'disponible' : 'loué'; }

    // Setters
    public function setId(int $id): self {
        if ($this->id !== null) {
            throw new \RuntimeException('L\'ID ne peut pas être modifié');
        }
        $this->id = $id;
        return $this;
    }

    public function setName(string $name): self {
        $name = trim($name);
        if (empty($name)) {
            throw new \InvalidArgumentException('Le nom de l\'équipement ne peut pas être vide');
        }
        if (strlen($name) < 3) {
            throw new \InvalidArgumentException('Le nom doit faire au moins 3 caractères');
        }
        $this->name = $name;
        return $this;
    }

    public function setCategory(Category $category): self {
        $this->category = $category;
        return $this;
    }

    public function setDailyRate(float $rate): self {
        if ($rate < 0) {
            throw new \InvalidArgumentException('Le taux journalier ne peut pas être négatif');
        }
        $this->dailyRate = $rate;
        return $this;
    }

    public function setAvailable(bool $available): self {
        if ($available && $this->needsMaintenance()) {
            throw new \RuntimeException('L\'équipement nécessite une maintenance avant de pouvoir être disponible');
        }
        $this->available = $available;
        return $this;
    }

    public function setLastMaintenance(?\DateTimeImmutable $date): self {
        if ($date && $date > new \DateTimeImmutable()) {
            throw new \InvalidArgumentException('La date de maintenance ne peut pas être dans le futur');
        }
        $this->lastMaintenance = $date;
        return $this;
    }

    public function setSerialNumber(?string $serialNumber): self {
        $this->serialNumber = $serialNumber ? strtoupper(trim($serialNumber)) : null;
        return $this;
    }

    // Méthodes métier
    public function markAsRented(): self {
        if (!$this->available) {
            throw new \RuntimeException('L\'équipement est déjà loué');
        }
        if ($this->needsMaintenance()) {
            throw new \RuntimeException('L\'équipement nécessite une maintenance avant d\'être loué');
        }
        $this->available = false;
        return $this;
    }

    public function markAsAvailable(): self {
        if ($this->available) {
            throw new \RuntimeException('L\'équipement est déjà disponible');
        }
        if ($this->needsMaintenance()) {
            throw new \RuntimeException('L\'équipement nécessite une maintenance avant d\'être disponible');
        }
        $this->available = true;
        return $this;
    }

    public function needsMaintenance(): bool {
        if (!$this->lastMaintenance) {
            return true;
        }

        $daysSinceMaintenance = $this->lastMaintenance->diff(new \DateTimeImmutable())->days;
        $threshold = $this->category->requiresMaintenance() ? 60 : 90;

        return $daysSinceMaintenance > $threshold;
    }

    public function getMaintenanceAlert(): ?string {
        if (!$this->needsMaintenance()) {
            return null;
        }

        $days = $this->lastMaintenance
            ? $this->lastMaintenance->diff(new \DateTimeImmutable())->days
            : 0;

        return sprintf(
            'Maintenance requise (dernière maintenance il y a %d jours)',
            $days
        );
    }

    public function getEffectiveDailyRate(): float {
        return $this->dailyRate * $this->category->getDailyRateMultiplier();
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category->getSlug(),
            'category_label' => $this->category->getName(),
            'category_icon' => $this->category->getIcon(),
            'category_color' => $this->category->getColor(),
            'daily_rate' => $this->dailyRate,
            'effective_daily_rate' => $this->getEffectiveDailyRate(),
            'available' => $this->available,
            'status' => $this->getStatus(),
            'last_maintenance' => $this->lastMaintenance?->format('Y-m-d H:i:s'),
            'needs_maintenance' => $this->needsMaintenance(),
            'maintenance_alert' => $this->getMaintenanceAlert(),
            'serial_number' => $this->serialNumber,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }

    public function __toString(): string {
        return sprintf(
            '%s (#%d) - %s - %.2f€/jour',
            $this->name,
            $this->id ?? 0,
            $this->category->getName(),
            $this->getEffectiveDailyRate()
        );
    }
}