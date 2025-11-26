<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VisiteurController extends AbstractController
{
    #[Route('/visiteur', name: 'app_visiteur')]
    public function dashboard(Request $request): Response
    {
        $session = $request->getSession();
        
        if (!$session->has('user_id') || $session->get('user_role') !== 'VISITEUR') {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('visiteur/dashboard.html.twig', [
            'user_name' => $session->get('user_name'),
            'user_role' => $session->get('user_role')
        ]);
    }
}