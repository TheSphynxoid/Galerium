<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function dashboard(Request $request): Response
    {
        $session = $request->getSession();
        
        if (!$session->has('user_id') || $session->get('user_role') !== 'ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('admin/dashboard.html.twig', [
            'user_name' => $session->get('user_name'),
            'user_role' => $session->get('user_role')
        ]);
    }
}