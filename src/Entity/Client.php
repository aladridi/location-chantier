<?php
namespace App\Entity;

use App\Attribute\Table;
use App\Attribute\Column;
use App\Attribute\Id;

#[Table('clients')]
class Client
{
    #[Id]
    #[Column('id')]
    private ?int $id = null;

    #[Column('first_name')]
    private string $firstName = '';

    #[Column('last_name')]
    private string $lastName = '';

    #[Column('email')]
    private string $email = '';

    #[Column('phone')]
    private ?string $phone = null;

    #[Column('company')]
    private ?string $company = null;

    #[Column('address')]
    private ?string $address = null;

    #[Column('city')]
    private ?string $city = null;

    #[Column('postal_code')]
    private ?string $postalCode = null;

    #[Column('created_at')]
    private ?\DateTimeImmutable $createdAt = null;

    #[Column('updated_at')]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct(
        string $firstName,
        string $lastName,
        string $email,
        ?string $phone = null,
        ?string $company = null,
        ?string $address = null,
        ?string $city = null,
        ?string $postalCode = null,
    ) {
        $this->setFirstName($firstName);
        $this->setLastName($lastName);
        $this->setEmail($email);
        $this->setPhone($phone);

        $this->company = $company;
        $this->address = $address;
        $this->city = $city;
        $this->postalCode = $postalCode;

        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Le nom complet est calculé : aucune colonne full_name n'est nécessaire.
    public function getFirstName(): string { return $this->firstName; }
    public function getLastName(): string { return $this->lastName; }
    public function getEmail(): string { return $this->email; }
    public function getPhone(): ?string { return $this->phone; }
    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function setLastName(string $lastName): self
    {
        $lastName = trim($lastName);
        if ($lastName === '') {
            throw new \InvalidArgumentException('Le nom ne peut pas être vide');
        }
        $this->lastName = $lastName;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    // Getters/Setters simples

    public function setEmail(string $email): self
    {
        $email = strtolower(trim($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email invalide');
        }

        $this->email = $email;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function setFirstName(string $firstName): self
    {
        $firstName = trim($firstName);

        if ($firstName === '') {
            throw new \InvalidArgumentException('Le prénom ne peut pas être vide');
        }

        $this->firstName = $firstName;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }
    public function setPhone(?string $phone): self
    {
        if ($phone === null || trim($phone) === '') {
            $this->phone = null;
            $this->updatedAt = new \DateTimeImmutable();
            return $this;
        }

        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($cleaned) === 10) {
            $phone =
                substr($cleaned, 0, 2) . ' ' .
                substr($cleaned, 2, 2) . ' ' .
                substr($cleaned, 4, 2) . ' ' .
                substr($cleaned, 6, 2) . ' ' .
                substr($cleaned, 8, 2);
        }

        $this->phone = trim($phone);
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }
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

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

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
            return $this->company . ' (' . $this->getFullName() . ')';
        }
        return $this->getFullName();
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
            'full_name' => $this->getFullName(),
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            'address' => $this->address,
            'city' => $this->city,
            'postal_code' => $this->postalCode,
            'full_address' => $this->getFullAddress(),
            'is_company' => $this->isCompany(),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}