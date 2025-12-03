<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArtisteController extends AbstractController
{
    #[Route('/artiste', name: 'app_artiste')]
    public function dashboard(Request $request): Response
    {
        $session = $request->getSession();
        
        if (!$session->has('user_id') || $session->get('user_role') !== 'ARTISTE') {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('artiste/dashboard.html.twig', [
            'user_name' => $session->get('user_name'),
            'user_role' => $session->get('user_role')
        ]);
    }
}