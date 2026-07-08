<?php
namespace App\Controller;

use App\Core\Http\Request;
use App\Core\Http\Response;

class VueController
{
    public function index(Request $request): Response
    {
        $pageTitle = 'Location Chantier - Gestion de matériel';

        ob_start();
        include __DIR__ . '/../../templates/vue/layout.php';
        $content = ob_get_clean();

        return new Response($content);
    }

    public function equipment(Request $request): Response
    {
        $pageTitle = 'Gestion des équipements';

        ob_start();
        include __DIR__ . '/../../templates/vue/equipment.php';
        $content = ob_get_clean();

        return new Response($content);
    }

    public function clients(Request $request): Response
    {
        $pageTitle = 'Gestion des clients';

        ob_start();
        include __DIR__ . '/../../templates/vue/clients.php';
        $content = ob_get_clean();

        return new Response($content);
    }

    public function rentals(Request $request): Response
    {
        $pageTitle = 'Gestion des locations';

        ob_start();
        include __DIR__ . '/../../templates/vue/rentals.php';
        $content = ob_get_clean();

        return new Response($content);
    }
}