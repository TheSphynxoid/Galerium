<?php

namespace App\Controller;

use App\Entity\Artiste;
use App\Entity\Oeuvre;
use App\Form\ArtisteType;
use App\Repository\ArtisteRepository;
use App\Repository\OeuvreRepository;
use App\Service\ArtisteService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UtilisateurRepository;

#[Route('/artiste')]
class ArtisteController extends AbstractController
{
    #[Route('', name: 'app_artiste_index', methods: ['GET'])]
    public function index(ArtisteRepository $repository): Response
    {
        return $this->render('artiste/index.html.twig', [
            'artistes' => $repository->findAll(),
        ]);
    }

    #[Route('/profile', name: 'app_artiste_profile', methods: ['GET', 'POST'])]
    public function profile(
        Request $request,
        ArtisteService $artisteService,
        UtilisateurRepository $userRepository
    ): Response {
        
        $session = $request->getSession();

        if (!$session->has('user_id')) {
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($session->get('user_id'));

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Récupération ou création de l'artiste lié
        $artiste = $user->getArtiste();
        if (!$artiste) {
            $artiste = new Artiste();
            $artiste->setUser($user);
        }

        $form = $this->createForm(ArtisteType::class, $artiste);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $artisteService->save($artiste);
            $this->addFlash('success', 'Profil mis à jour avec succès!');
            return $this->redirectToRoute('app_artiste_dashboard');
        }

        return $this->render('artiste/profile.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/dashboard', name: 'app_artiste_dashboard', methods: ['GET'])]
    public function dashboard(
        Request $request,
        OeuvreRepository $oeuvreRepository, 
        EntityManagerInterface $entityManager,
        UtilisateurRepository $userRepository
    ): Response {
        // Utiliser la même authentification que profile()
        $session = $request->getSession();

        if (!$session->has('user_id')) {
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($session->get('user_id'));

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $artiste = $user->getArtiste();

        if (!$artiste) {
            return $this->redirectToRoute('app_artiste_profile');
        }

        $entityManager->refresh($artiste);

        $oeuvres = $oeuvreRepository->findByArtiste($artiste);

        $totalViews = 0;
        $totalVotes = 0;
        $totalFavorites = 0;

        foreach ($oeuvres as $oeuvre) {
            $totalViews += $oeuvre->getViewsCount();
            $totalVotes += $oeuvre->getVotesCount();
            $totalFavorites += $oeuvre->getFavoritesCount();
        }

        return $this->render('artiste/dashboard.html.twig', [
            'artiste' => $artiste,
            'oeuvres' => $oeuvres,
            'totalViews' => $totalViews,
            'totalVotes' => $totalVotes,
            'totalFavorites' => $totalFavorites,
        ]);
    }

    #[Route('/{id}', name: 'app_artiste_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        Artiste $artiste,
        OeuvreRepository $oeuvreRepository,
    ): Response {
        return $this->render('artiste/show.html.twig', [
            'artiste' => $artiste,
            'oeuvres' => $oeuvreRepository->findBy([
                'artiste' => $artiste,
                'status' => Oeuvre::STATUS_PUBLIC,
            ]),
        ]);
    }
}