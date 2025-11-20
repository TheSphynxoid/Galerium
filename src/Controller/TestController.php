<?php

namespace App\Controller;

use App\Repository\ConcoursRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TestController extends AbstractController
{
    #[Route('/test', name: 'app_test')]
    public function index(ConcoursRepository $concoursRepository): Response
    {
        return $this->render('test/index.html.twig', [
            'concours' => $concoursRepository->findBy([], ['dateDebut' => 'DESC']),
        ]);
    }

    #[Route('/test/backoffice', name: 'app_test_backoffice')]
    public function TestBackoffice(): Response
    {
        return $this->render('test/backoffice.html.twig', [
            'controller_name' => 'TestController',
        ]);
    }
}
