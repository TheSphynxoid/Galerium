<?php

namespace App\Controller;

use App\Repository\UtilisateurRepository;
use App\Repository\ConcoursRepository;
use App\Repository\OeuvreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/visiteur')]
class VisiteurController extends AbstractController
{
    private UtilisateurRepository $userRepository;
    private ConcoursRepository $concoursRepository;
    private OeuvreRepository $oeuvreRepository;

    public function __construct(
        UtilisateurRepository $userRepository,
        ConcoursRepository $concoursRepository,
        OeuvreRepository $oeuvreRepository
    )
    {
        $this->userRepository = $userRepository;
        $this->concoursRepository = $concoursRepository;
        $this->oeuvreRepository = $oeuvreRepository;
    }

    // --- Index visiteur ---
    #[Route('/', name: 'app_visiteur')]
    public function index(Request $request): Response
    {
        $session = $request->getSession();

        // Vérification de connexion
        if (!$session->has('user_id') || $session->get('user_role') !== 'VISITEUR') {
            return $this->redirectToRoute('app_login');
        }

        // Récupération des concours actifs (derniers)
        $recentConcours = $this->concoursRepository->findActifs();
        if (count($recentConcours) > 6) {
            $recentConcours = array_slice($recentConcours, 0, 6);
        }

        // Récupération des oeuvres publiques récentes
        $recentOeuvres = $this->oeuvreRepository->search(limit: 6);

        return $this->render('visiteur/index.html.twig', [
            'user_name' => $session->get('user_name'),
            'recentConcours' => $recentConcours,
            'recentOeuvres' => $recentOeuvres,
        ]);
    }

    // --- Dashboard visiteur ---
    #[Route('/dashboard', name: 'app_visiteur_dashboard')]
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
