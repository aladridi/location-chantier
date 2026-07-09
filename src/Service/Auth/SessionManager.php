<?php
namespace App\Service\Auth;

use App\Entity\User;

class SessionManager
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login(User $user): void
    {
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['username'] = $user->getUsername();
        $_SESSION['email'] = $user->getEmail();
        $_SESSION['roles'] = $user->getRoles();  // ✅ Maintenant cette méthode existe
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public function getUsername(): ?string
    {
        return $_SESSION['username'] ?? null;
    }

    public function getRoles(): array
    {
        return $_SESSION['roles'] ?? ['ROLE_USER'];
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('ROLE_ADMIN');
    }

    public function getSessionData(): array
    {
        return [
            'user_id' => $this->getUserId(),
            'username' => $this->getUsername(),
            'roles' => $this->getRoles(),
            'logged_in' => $this->isLoggedIn(),
            'is_admin' => $this->isAdmin()
        ];
    }

    public function regenerateSession(): void
    {
        session_regenerate_id(true);
    }
}