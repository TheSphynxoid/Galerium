<?php

namespace App\Controller;

use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/visiteur')]
class VisiteurController extends AbstractController
{
    private UtilisateurRepository $userRepository;

    public function __construct(UtilisateurRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    // --- Dashboard visiteur ---
    #[Route('/visiteur', name: 'app_visiteur')]
    public function dashboard(Request $request): Response
    {
        $session = $request->getSession();

        // Vérification de connexion
        if (!$session->has('user_id') || $session->get('user_role') !== 'VISITEUR') {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('visiteur/dashboard.html.twig', [
            'user_name' => $session->get('user_name'),
            'user_email' => $session->get('user_email'),
            'user_role' => $session->get('user_role'),
        ]);
    }

    // --- Profil visiteur ---
    #[Route('/visiteur/profil', name: 'visiteur_profil')]
    public function profil(Request $request): Response
    {
        $session = $request->getSession();

        if (!$session->has('user_id') || $session->get('user_role') !== 'VISITEUR') {
            return $this->redirectToRoute('app_login');
        }

        // Récupération de l'utilisateur depuis la DB
        $user = $this->userRepository->find($session->get('user_id'));

        if (!$user) {
            $session->clear();
            return $this->redirectToRoute('app_login');
        }

        return $this->render('visiteur/profil.html.twig', [
            'user' => $user
        ]);
    }
}
