<?php
namespace App\Middleware;

use App\Service\Auth\AuthService;
use App\Core\Http\Request;
use App\Core\Http\Response;

class AuthMiddleware
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function handle(Request $request, callable $next)
    {
        // Vérifier si l'utilisateur est authentifié
        if (!$this->authService->isAuthenticated()) {
            // Sauvegarder l'URL demandée pour redirection après login
            $_SESSION['redirect_after_login'] = $request->getUri();

            // Rediriger vers la page de login
            $response = new Response();
            $response->setHeader('Location', '/login');
            $response->send();
            exit;
        }

        // Vérifier si le compte est toujours actif
        $user = $this->authService->getCurrentUser();
        if ($user && !$user->isActive()) {
            $this->authService->logout();
            $response = new Response('Votre compte a été désactivé', 403);
            $response->send();
            exit;
        }

        return $next($request);
    }

    public function handleApi(Request $request, callable $next)
    {
        // Pour les API, on peut utiliser un token ou vérifier la session
        if (!$this->authService->isAuthenticated()) {
            $response = new Response(json_encode([
                'error' => 'Non authentifié',
                'code' => 401
            ]), 401);
            $response->setHeader('Content-Type', 'application/json');
            $response->send();
            exit;
        }

        return $next($request);
    }

    public function requireRole(string $role, Request $request, callable $next)
    {
        if (!$this->authService->hasRole($role)) {
            $response = new Response('Accès non autorisé', 403);
            $response->send();
            exit;
        }

        return $next($request);
    }

    public function requireAdmin(Request $request, callable $next)
    {
        return $this->requireRole('ROLE_ADMIN', $request, $next);
    }
}