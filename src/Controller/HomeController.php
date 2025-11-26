<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // Si l'utilisateur est connecté, rediriger vers son profil
        if ($this->getUser()) {
            return $this->redirectToRoute('app_artiste_profile');
        }

        // Sinon, rediriger vers la page de connexion
        return $this->redirectToRoute('app_artiste_login');
    }
}






