<?php
namespace App\Entity;

use App\Entity\Enum\EquipmentCategory;
use App\Core\Validator\Constraints as Assert;
use App\Entity\Category;


/**
 * Classe Equipment avec les nouveautés PHP 8.4
 *
 * Utilisation de :
 * - Constructor property promotion
 * - Hooks (get/set)
 * - Asymmetrical visibility
 * - Readonly pour les propriétés immutables
 */
class Equipment
{
    // Propriété avec asymétrique visibility (PHP 8.4)
    // Lecture publique, écriture privée
    public private(set) ?int $id = null;

    public private(set) \DateTimeImmutable $createdAt;

    // Hook pour la propriété 'status' (PHP 8.4)
    // Calculé dynamiquement à partir de 'available'
    public string $status {
        get {
            return $this->available ? 'disponible' : 'loué';
        }
    }

    // Hook avec setter personnalisé pour le taux quotidien

    public float $dailyRate {
        get => $this->dailyRate;
        set (float $value) {
            if ($value < 0) {
                throw new \InvalidArgumentException('Le taux journalier ne peut pas être négatif');
            }
            $this->dailyRate = $value;
        }
    }

    // Hook avec validation pour le nom

    public string $name {
        get => $this->name;
        set (string $value) {
            $value = trim($value);
            if (empty($value)) {
                throw new \InvalidArgumentException('Le nom de l\'équipement ne peut pas être vide');
            }
            if (strlen($value) < 3) {
                throw new \InvalidArgumentException('Le nom doit faire au moins 3 caractères');
            }
            $this->name = $value;
        }
    }

    // Hook avec transformation automatique

    public ?string $serialNumber {
        get => $this->serialNumber;
        set (?string $value) {
            // Mettre en majuscules si présent
            $this->serialNumber = $value ? strtoupper(trim($value)) : null;
        }
    }

    private Category $category {
        get => $this->category;
                set (Category $value) {
            $this->category = $value;
        }
    }

    public function __construct(
        string $name,
        Category $category,
        float $dailyRate,
        private bool $available = true,
        private ?\DateTimeImmutable $lastMaintenance = null,
        ?string $serialNumber = null,
    ) {
        // Utilisation des hooks via les setters
        $this->name = $name;
        $this->category = $category;
        $this->dailyRate = $dailyRate;
        $this->available = $available;
        $this->lastMaintenance = $lastMaintenance ?? new \DateTimeImmutable();
        $this->serialNumber = $serialNumber;
        $this->createdAt = new \DateTimeImmutable();
    }

    // Propriétés avec getters/setters simples (pour les valeurs scalaires)
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        // L'ID ne peut être défini qu'une seule fois
        if ($this->id !== null) {
            throw new \RuntimeException('L\'ID ne peut pas être modifié');
        }
        $this->id = $id;
        return $this;
    }

    public function getCategory(): EquipmentCategory
    {
        return $this->category;
    }

    public function setCategory(EquipmentCategory $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function setAvailable(bool $available): self
    {
        // On ne peut pas rendre disponible si la maintenance est requise
        if ($available && $this->needsMaintenance()) {
            throw new \RuntimeException('L\'équipement nécessite une maintenance avant de pouvoir être disponible');
        }
        $this->available = $available;
        return $this;
    }

    public function getLastMaintenance(): ?\DateTimeImmutable
    {
        return $this->lastMaintenance;
    }

    public function setLastMaintenance(?\DateTimeImmutable $date): self
    {
        // Validation : la date ne peut pas être dans le futur
        if ($date && $date > new \DateTimeImmutable()) {
            throw new \InvalidArgumentException('La date de maintenance ne peut pas être dans le futur');
        }
        $this->lastMaintenance = $date;
        return $this;
    }

    // Méthodes métier
    public function markAsRented(): self
    {
        if (!$this->available) {
            throw new \RuntimeException('L\'équipement est déjà loué');
        }
        if ($this->needsMaintenance()) {
            throw new \RuntimeException('L\'équipement nécessite une maintenance avant d\'être loué');
        }
        $this->available = false;
        return $this;
    }

    public function markAsAvailable(): self
    {
        if ($this->available) {
            throw new \RuntimeException('L\'équipement est déjà disponible');
        }
        if ($this->needsMaintenance()) {
            throw new \RuntimeException('L\'équipement nécessite une maintenance avant d\'être disponible');
        }
        $this->available = true;
        return $this;
    }

    public function needsMaintenance(): bool
    {
        if (!$this->lastMaintenance) {
            return true;
        }

        $daysSinceMaintenance = $this->lastMaintenance->diff(new \DateTimeImmutable())->days;
        $threshold = $this->category->requiresMaintenance() ? 60 : 90;

        return $daysSinceMaintenance > $threshold;
    }

    public function getMaintenanceAlert(): ?string
    {
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

    // Méthode utilitaire pour la tarification
    public function getEffectiveDailyRate(): float
    {
        return $this->dailyRate * $this->category->getDailyRateMultiplier();
    }

    // Méthode de représentation (utile pour le debug)
    public function __toString(): string
    {
        return sprintf(
            '%s (#%d) - %s - %.2f€/jour',
            $this->name,
            $this->id ?? 0,
            $this->category->getLabel(),
            $this->getEffectiveDailyRate()
        );
    }

    // Méthode pour la sérialisation (JSON)
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category->value,
            'category_label' => $this->category->getLabel(),
            'daily_rate' => $this->dailyRate,
            'effective_daily_rate' => $this->getEffectiveDailyRate(),
            'available' => $this->available,
            'status' => $this->status,
            'last_maintenance' => $this->lastMaintenance?->format('Y-m-d H:i:s'),
            'needs_maintenance' => $this->needsMaintenance(),
            'maintenance_alert' => $this->getMaintenanceAlert(),
            'serial_number' => $this->serialNumber,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}