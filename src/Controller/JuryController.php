<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class JuryController extends AbstractController
{
    #[Route('/jury', name: 'app_jury')]
    public function dashboard(Request $request): Response
    {
        $session = $request->getSession();
        
        if (!$session->has('user_id') || $session->get('user_role') !== 'JURY') {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('jury/dashboard.html.twig', [
            'user_name' => $session->get('user_name'),
            'user_role' => $session->get('user_role')
        ]);
    }
}