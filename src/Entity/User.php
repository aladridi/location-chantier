<?php
namespace App\Entity;

class User
{
    private ?int $id = null;
    private string $username;
    private string $email;
    private string $passwordHash;
    private array $roles = ['ROLE_USER'];
    private bool $active = true;
    private ?\DateTimeImmutable $lastLogin = null;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $username,
        string $email,
        string $passwordHash,
        array $roles = ['ROLE_USER']
    ) {
        $this->username = $username;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->roles = $roles;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getEmail(): string { return $this->email; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    public function getRoles(): array { return $this->roles; }
    public function isActive(): bool { return $this->active; }
    public function getLastLogin(): ?\DateTimeImmutable { return $this->lastLogin; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    // Setters
    public function setId(int $id): self {
        if ($this->id !== null) {
            throw new \RuntimeException('L\'ID ne peut pas être modifié');
        }
        $this->id = $id;
        return $this;
    }

    public function setUsername(string $username): self {
        $username = trim($username);
        if (empty($username)) {
            throw new \InvalidArgumentException('Le nom d\'utilisateur ne peut pas être vide');
        }
        if (strlen($username) < 3) {
            throw new \InvalidArgumentException('Le nom d\'utilisateur doit faire au moins 3 caractères');
        }
        $this->username = $username;
        return $this;
    }

    public function setEmail(string $email): self {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email invalide');
        }
        $this->email = $email;
        return $this;
    }

    public function setPasswordHash(string $passwordHash): self {
        $this->passwordHash = $passwordHash;
        return $this;
    }

    public function setRoles(array $roles): self {
        $this->roles = array_unique($roles);
        return $this;
    }

    public function setActive(bool $active): self {
        $this->active = $active;
        return $this;
    }

    public function setLastLogin(?\DateTimeImmutable $lastLogin): self {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    // Méthodes métier
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('ROLE_ADMIN');
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'roles' => $this->roles,
            'active' => $this->active,
            'last_login' => $this->lastLogin?->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}