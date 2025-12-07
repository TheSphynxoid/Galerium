<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/visiteur')]
class VisiteurController extends AbstractController
{
    #[Route('', name: 'app_visiteur')]
    public function index(): Response
    {
        return $this->render('visiteur/index.html.twig');
    }
}