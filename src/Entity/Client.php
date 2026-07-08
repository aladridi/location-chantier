<?php
namespace App\Entity;

use App\Core\Validator\Constraints as Assert;

class Client
{
    // Asymétrique visibility pour l'ID
    public private(set) ?int $id = null;

    public private(set) \DateTimeImmutable $createdAt;
    public private(set) \DateTimeImmutable $updatedAt;

    // Hook avec validation email

    public string $email {
        get => $this->email;
        set (string $value) {
            $value = strtolower(trim($value));
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException('Email invalide');
            }
            $this->email = $value;
        }
    }

    // Hook avec transformation pour les noms

    public string $firstName {
        get => $this->firstName;
        set (string $value) {
            $value = trim($value);
            if (empty($value)) {
                throw new \InvalidArgumentException('Le prénom ne peut pas être vide');
            }
            // Capitalisation
            $this->firstName = ucfirst(strtolower($value));
        }
    }


    public string $lastName {
        get => $this->lastName;
        set (string $value) {
            $value = trim($value);
            if (empty($value)) {
                throw new \InvalidArgumentException('Le nom ne peut pas être vide');
            }
            // Mettre en majuscules
            $this->lastName = strtoupper($value);
        }
    }

    // Hook avec formatage du téléphone

    public ?string $phone {
        get => $this->phone;
        set (?string $value) {
            if ($value === null || $value === '') {
                $this->phone = null;
                return;
            }
            // Supprimer tous les caractères non numériques
            $cleaned = preg_replace('/[^0-9]/', '', $value);

            // Format français : 0X XX XX XX XX
            if (strlen($cleaned) === 10) {
                $this->phone = substr($cleaned, 0, 2) . ' ' .
                    substr($cleaned, 2, 2) . ' ' .
                    substr($cleaned, 4, 2) . ' ' .
                    substr($cleaned, 6, 2) . ' ' .
                    substr($cleaned, 8, 2);
            } else {
                $this->phone = $value;
            }
        }
    }

    // Propriété calculée avec hook (PHP 8.4)
    public string $fullName {
        get => $this->firstName . ' ' . $this->lastName;
    }

    public function __construct(
        string $firstName,
        string $lastName,
        string $email,
        ?string $phone = null,
        private ?string $company = null,
        private ?string $address = null,
        private ?string $city = null,
        private ?string $postalCode = null,
    ) {
        // Utilisation des hooks via les setters
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->phone = $phone;
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

    public function getCompany(): ?string { return $this->company; }
    public function setCompany(?string $company): self
    {
        $this->company = $company;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $address): self
    {
        $this->address = $address;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCity(): ?string { return $this->city; }
    public function setCity(?string $city): self
    {
        $this->city = $city;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getPostalCode(): ?string { return $this->postalCode; }
    public function setPostalCode(?string $postalCode): self
    {
        $this->postalCode = $postalCode;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    // Méthode pour obtenir l'adresse complète
    public function getFullAddress(): ?string
    {
        if (!$this->address && !$this->city) {
            return null;
        }

        $parts = array_filter([
            $this->address,
            $this->postalCode,
            $this->city,
        ]);

        return implode(', ', $parts);
    }

    // Méthode métier : vérifier si le client est une entreprise
    public function isCompany(): bool
    {
        return $this->company !== null && $this->company !== '';
    }

    // Méthode métier : obtenir le nom affichable
    public function getDisplayName(): string
    {
        if ($this->isCompany()) {
            return $this->company . ' (' . $this->fullName . ')';
        }
        return $this->fullName;
    }

    // Représentation
    public function __toString(): string
    {
        return $this->getDisplayName();
    }

    // Sérialisation pour JSON
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'full_name' => $this->fullName,
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            'address' => $this->address,
            'city' => $this->city,
            'postal_code' => $this->postalCode,
            'full_address' => $this->getFullAddress(),
            'is_company' => $this->isCompany(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}