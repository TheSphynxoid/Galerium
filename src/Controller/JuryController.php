<?php

namespace App\Controller;

use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class JuryController extends AbstractController
{
    private UtilisateurRepository $userRepository;

    public function __construct(UtilisateurRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    #[Route('/jury', name: 'app_jury')]
    public function dashboard(Request $request): Response
    {
        $session = $request->getSession();

        if (!$session->has('user_id') || $session->get('user_role') !== 'JURY') {
            return $this->redirectToRoute('app_login');
        }

        // Récupération de l'utilisateur Jury depuis la DB
        $user = $this->userRepository->find($session->get('user_id'));

        if (!$user) {
            $session->clear();
            return $this->redirectToRoute('app_login');
        }

        return $this->render('jury/dashboard.html.twig', [
            'user' => $user
        ]);
    }
}
