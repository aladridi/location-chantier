<?php
namespace App\Service\Auth;

use App\Entity\User;
use App\Repository\UserRepository;

class AuthService
{
    public function __construct(
        private UserRepository $userRepository,
        private PasswordHasher $passwordHasher,
        private SessionManager $sessionManager
    ) {}

    public function login(string $identifier, string $password): ?User
    {
        // Log du début
        error_log("🔐 Tentative de connexion pour: " . $identifier);

        // Chercher par email ou username
        $user = $this->userRepository->findByEmail($identifier);

        if (!$user) {
            $user = $this->userRepository->findByUsername($identifier);
        }

        if (!$user) {
            error_log("❌ Utilisateur non trouvé: " . $identifier);
            return null;
        }

        if (!$user->isActive()) {
            throw new \RuntimeException('Ce compte est désactivé');
        }

        // ✅ CORRIGÉ : Utiliser le getter au lieu d'accéder directement à la propriété
        $passwordHash = $user->getPasswordHash();

        error_log("📝 Hash stocké: " . substr($passwordHash, 0, 40) . "...");
        error_log("📝 Longueur du hash: " . strlen($passwordHash));

        // Vérifier le mot de passe
        $isValid = $this->passwordHasher->verify($password, $passwordHash);

        error_log("🔑 Résultat de la vérification: " . ($isValid ? '✅ VALIDE' : '❌ INVALIDE'));

        if (!$isValid) {
            // Vérifier le format du hash
            $info = password_get_info($passwordHash);
            error_log("📊 Info du hash:");
            error_log("  - Algo: " . $info['algoName']);
            error_log("  - Algo ID: " . $info['algo']);
            error_log("  - Options: " . print_r($info['options'], true));

            return null;
        }

        // ✅ CORRIGÉ : Utiliser le getter pour vérifier si le hash doit être ré-hashé
        if ($this->passwordHasher->needsRehash($passwordHash)) {
            $newHash = $this->passwordHasher->hash($password);
            $this->userRepository->updatePassword($user->getId(), $newHash);
            error_log("🔄 Hash ré-généré pour: " . $user->getUsername());
        }

        // Mettre à jour la date de dernière connexion
        $this->userRepository->updateLastLogin($user->getId());

        // Démarrer la session
        $this->sessionManager->login($user);
        $this->sessionManager->regenerateSession();

        error_log("✅ Connexion réussie pour: " . $user->getUsername());
        return $user;
    }

    public function logout(): void
    {
        $this->sessionManager->logout();
    }

    public function isAuthenticated(): bool
    {
        return $this->sessionManager->isLoggedIn();
    }

    public function getCurrentUser(): ?User
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        $userId = $this->sessionManager->getUserId();
        if (!$userId) {
            return null;
        }

        return $this->userRepository->find($userId);
    }

    public function getCurrentUserData(): ?array
    {
        $user = $this->getCurrentUser();
        return $user ? $user->toArray() : null;
    }

    public function hasRole(string $role): bool
    {
        return $this->sessionManager->hasRole($role);
    }

    public function isAdmin(): bool
    {
        return $this->sessionManager->isAdmin();
    }
}