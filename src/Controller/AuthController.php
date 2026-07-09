<?php
namespace App\Controller;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Service\Auth\AuthService;
use App\Service\Auth\PasswordHasher;
use App\Repository\UserRepository;

class AuthController
{
    public function __construct(
        private AuthService $authService,
        private PasswordHasher $passwordHasher,
        private UserRepository $userRepository
    ) {}

    public function login(Request $request): Response
    {
        // Si déjà connecté, rediriger vers le dashboard
        if ($this->authService->isAuthenticated()) {
            $response = new Response();
            $response->setHeader('Location', '/dashboard');
            $response->send();
            exit;
        }

        if ($request->getMethod() === 'POST') {
            $data = $request->toArray();
            $identifier = $data['identifier'] ?? '';
            $password = $data['password'] ?? '';
            $remember = isset($data['remember']);

            if (empty($identifier) || empty($password)) {
                $error = 'Veuillez remplir tous les champs';
                return $this->renderLogin($error);
            }

            try {
                $user = $this->authService->login($identifier, $password);

                if (!$user) {
                    $error = 'Identifiant ou mot de passe incorrect';
                    return $this->renderLogin($error);
                }

                // Redirection après login
                $redirect = $_SESSION['redirect_after_login'] ?? '/dashboard';
                unset($_SESSION['redirect_after_login']);

                $response = new Response();
                $response->setHeader('Location', $redirect);
                $response->send();
                exit;
            } catch (\Exception $e) {
                $error = $e->getMessage();
                return $this->renderLogin($error);
            }
        }

        return $this->renderLogin();
    }

    private function renderLogin(?string $error = null): Response
    {
        ob_start();
        include __DIR__ . '/../../templates/auth/login.php';
        $content = ob_get_clean();

        return new Response($content);
    }

    public function logout(Request $request): Response
    {
        $this->authService->logout();

        $response = new Response();
        $response->setHeader('Location', '/login');
        $response->send();
        exit;
    }

    public function register(Request $request): Response
    {
        if ($request->getMethod() === 'POST') {
            $data = $request->toArray();
            $username = $data['username'] ?? '';
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';
            $passwordConfirm = $data['password_confirm'] ?? '';

            // Validation
            if (empty($username) || empty($email) || empty($password)) {
                $error = 'Tous les champs sont obligatoires';
                return $this->renderRegister($error);
            }

            if ($password !== $passwordConfirm) {
                $error = 'Les mots de passe ne correspondent pas';
                return $this->renderRegister($error);
            }

            if (strlen($password) < 8) {
                $error = 'Le mot de passe doit faire au moins 8 caractères';
                return $this->renderRegister($error);
            }

            try {
                // Vérifier si l'email existe déjà
                $existingUser = $this->userRepository->findByEmail($email);
                if ($existingUser) {
                    $error = 'Cet email est déjà utilisé';
                    return $this->renderRegister($error);
                }

                // Vérifier si le username existe déjà
                $existingUser = $this->userRepository->findByUsername($username);
                if ($existingUser) {
                    $error = 'Ce nom d\'utilisateur est déjà utilisé';
                    return $this->renderRegister($error);
                }

                // Créer l'utilisateur
                $passwordHash = $this->passwordHasher->hash($password);
                $user = $this->userRepository->createUser($username, $email, $passwordHash);

                // Connecter automatiquement
                $this->authService->login($user);

                $response = new Response();
                $response->setHeader('Location', '/dashboard');
                $response->send();
                exit;
            } catch (\Exception $e) {
                $error = $e->getMessage();
                return $this->renderRegister($error);
            }
        }

        return $this->renderRegister();
    }

    private function renderRegister(?string $error = null): Response
    {
        ob_start();
        include __DIR__ . '/../../templates/auth/register.php';
        $content = ob_get_clean();

        return new Response($content);
    }
}