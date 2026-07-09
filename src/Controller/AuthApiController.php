<?php
namespace App\Controller;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Service\Auth\AuthService;
use App\Service\Auth\PasswordHasher;
use App\Repository\UserRepository;

class AuthApiController
{
    public function __construct(
        private AuthService $authService,
        private PasswordHasher $passwordHasher,
        private UserRepository $userRepository
    ) {}

    public function login(Request $request): Response
    {
        $data = $request->toArray();
        $identifier = $data['identifier'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            return (new Response())->json([
                'error' => 'Veuillez remplir tous les champs'
            ], 400);
        }

        try {
            $user = $this->authService->login($identifier, $password);


            if (!$user) {
                return (new Response())->json([
                    'error' => 'Identifiant ou mot de passe incorrect'
                ], 401);
            }

            // Générer un token (JWT ou session)
            $token = $this->generateToken($user);

            return (new Response())->json([
                'success' => true,
                'data' => [
                    'user' => $user->toArray(),
                    'token' => $token
                ]
            ]);
        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function register(Request $request): Response
    {
        $data = $request->toArray();
        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $passwordConfirm = $data['password_confirm'] ?? '';

        // Validation
        if (empty($username) || empty($email) || empty($password)) {
            return (new Response())->json([
                'error' => 'Tous les champs sont obligatoires'
            ], 400);
        }

        if ($password !== $passwordConfirm) {
            return (new Response())->json([
                'error' => 'Les mots de passe ne correspondent pas'
            ], 400);
        }

        if (strlen($password) < 8) {
            return (new Response())->json([
                'error' => 'Le mot de passe doit faire au moins 8 caractères'
            ], 400);
        }

        try {
            // Vérifier si l'email existe déjà
            if ($this->userRepository->findByEmail($email)) {
                return (new Response())->json([
                    'error' => 'Cet email est déjà utilisé'
                ], 400);
            }

            // Vérifier si le username existe déjà
            if ($this->userRepository->findByUsername($username)) {
                return (new Response())->json([
                    'error' => 'Ce nom d\'utilisateur est déjà utilisé'
                ], 400);
            }

            // Créer l'utilisateur
            $passwordHash = $this->passwordHasher->hash($password);
            $user = $this->userRepository->createUser($username, $email, $passwordHash);

            // Connecter automatiquement
            $this->authService->login($user);
            $token = $this->generateToken($user);

            return (new Response())->json([
                'success' => true,
                'data' => [
                    'user' => $user->toArray(),
                    'token' => $token
                ]
            ], 201);
        } catch (\Exception $e) {
            return (new Response())->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function me(Request $request): Response
    {
        $user = $this->authService->getCurrentUser();

        if (!$user) {
            return (new Response())->json([
                'error' => 'Non authentifié'
            ], 401);
        }

        return (new Response())->json([
            'success' => true,
            'data' => $user->toArray()
        ]);
    }

    public function logout(Request $request): Response
    {
        $this->authService->logout();

        return (new Response())->json([
            'success' => true,
            'message' => 'Déconnecté avec succès'
        ]);
    }

    private function generateToken($user): string
    {
        // Simple session-based token
        // En production, utilisez JWT ou un système plus sécurisé
        return base64_encode(json_encode([
            'user_id' => $user->getId(),
            'username' => $user->getUsername(),
            'expires' => time() + 3600 * 24 // 24 heures
        ]));
    }
}