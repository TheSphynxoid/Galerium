<?php

namespace App\Controller;

use App\Entity\Participation;
use App\Form\ParticipationType;
use App\Repository\ParticipationRepository;
use App\Repository\ConcoursRepository;
use App\Repository\OeuvreRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/participation')]
final class ParticipationController extends AbstractController
{
    #[Route('/', name: 'app_participation_index', methods: ['GET'])]
    public function index(ParticipationRepository $participationRepository): Response
    {
        return $this->render('participation/index.html.twig', [
            'participations' => $participationRepository->findAll(),
        ]);
    }

    // Nouvelle méthode "new" avec association au concours
    #[Route('/new/{concoursId}', name: 'app_participation_new', methods: ['GET', 'POST'])]
    public function new(
        int $concoursId,
        Request $request,
        EntityManagerInterface $entityManager,
        ConcoursRepository $concoursRepository,
        OeuvreRepository $oeuvreRepository,
        UtilisateurRepository $userRepository
    ): Response {
        // Utiliser l'authentification par session comme dans OeuvreController
        $session = $request->getSession();

        if (!$session->has('user_id')) {
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($session->get('user_id'));

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer l'artiste associé à l'utilisateur
        $artiste = $user->getArtiste();
        if (!$artiste) {
            $this->addFlash('warning', 'Veuillez d\'abord compléter votre profil artiste.');
            return $this->redirectToRoute('app_concours_artistev_index');
        }

        $concour = $concoursRepository->find($concoursId);

        if (!$concour) {
            throw $this->createNotFoundException('Concours non trouvé');
        }

        // Récupérer les œuvres de l'artiste connecté
        $oeuvres = $oeuvreRepository->findByArtiste($artiste);

        if (empty($oeuvres)) {
            $this->addFlash('warning', 'Vous devez avoir au moins une œuvre pour participer à un concours.');
            return $this->redirectToRoute('app_concours_artistev_index');
        }

        $participation = new Participation();
        $participation->setDateParticipation(new \DateTime());
        $participation->setStatut('En cours'); // par défaut

        // On associe automatiquement le concours à la participation
        $participation->addConcour($concour);

        $form = $this->createForm(ParticipationType::class, $participation, [
            'oeuvres' => $oeuvres
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($participation);
            $entityManager->flush();

            $this->addFlash('success', 'Votre participation a été enregistrée !');
            return $this->redirectToRoute('app_concours_artistev_index');
        }

        return $this->render('participation/new.html.twig', [
            'participation' => $participation,
            'concour' => $concour,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_participation_show', methods: ['GET'])]
    public function show(Participation $participation): Response
    {
        return $this->render('participation/show.html.twig', [
            'participation' => $participation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_participation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Participation $participation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ParticipationType::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_participation_index');
        }

        return $this->render('participation/edit.html.twig', [
            'participation' => $participation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_participation_delete', methods: ['POST'])]
    public function delete(Request $request, Participation $participation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$participation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($participation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_participation_index');
    }
}
