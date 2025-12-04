<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        // Si l'utilisateur est connecté, redirige vers le dashboard
        if ($this->getUser()) {
            return $this->redirectToRoute('dashboard');
        }

        // Sinon, redirige vers la page de login
        return $this->redirectToRoute('app_login');
    }
}
