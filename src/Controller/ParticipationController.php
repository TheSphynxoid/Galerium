<?php

namespace App\Controller;

use App\Entity\Participation;
use App\Form\ParticipationType;
use App\Form\ParticipationEditType;
use App\Form\ParticipationStatusEditType;
use App\Repository\ParticipationRepository;
use App\Repository\ConcoursRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\OeuvreRepository;
use App\Repository\VoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/participation')]
final class ParticipationController extends AbstractController
{
    #[Route(name: 'app_participation_index', methods: ['GET'])]
    public function index(
        ParticipationRepository $participationRepository,
        ConcoursRepository $concoursRepository,
        VoteRepository $voteRepository
    ): Response {
        $participations = $participationRepository->findAll();
        
        // Compter les votes pour chaque participation depuis la base de données
        $votesCount = [];
        foreach ($participations as $participation) {
            $votesCount[$participation->getId()] = $voteRepository->countVotesForParticipation($participation);
        }
        
        return $this->render('participation/index.html.twig', [
            'participations' => $participations,
            'concoursList' => $concoursRepository->findAll(),
            'votesCount' => $votesCount,
        ]);
    }

    #[Route('/new/{concoursId}', name: 'app_participation_new', methods: ['GET', 'POST'])]
    public function new(
        int $concoursId,
        Request $request,
        EntityManagerInterface $entityManager,
        ConcoursRepository $concoursRepository,
        UtilisateurRepository $userRepository,
        OeuvreRepository $oeuvreRepository
    ): Response {
        // Vérifier que l'utilisateur est connecté
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($session->get('user_id'));
        if (!$user || !in_array('ROLE_ARTISTE', $user->getRoles())) {
            $this->addFlash("error", "Vous devez être un artiste pour participer à un concours.");
            return $this->redirectToRoute('app_participation_index');
        }

        $artiste = $user->getArtiste();
        if (!$artiste) {
            $this->addFlash("error", "Profil artiste introuvable.");
            return $this->redirectToRoute('app_participation_index');
        }

        $concours = $concoursRepository->find($concoursId);
        if (!$concours) {
            throw $this->createNotFoundException("Concours introuvable !");
        }

        $participation = new Participation();

        // ✅ Initialise les champs pour éviter NOT NULL
        $participation->setDateparticipation(new \DateTime());
        $participation->setStatut('en_cours');
        $participation->setVotepublic(false); // valeur réelle en base
        $participation->addConcour($concours);

        $form = $this->createForm(ParticipationType::class, $participation, [
            'artiste' => $artiste,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($participation);
            $entityManager->flush();

            $this->addFlash("success", "Participation envoyée avec succès !");
            return $this->redirectToRoute('app_concours_artistev_index');
        }

        // Récupérer les œuvres de l'artiste pour l'affichage visuel
        $oeuvres = $oeuvreRepository->findByArtiste($artiste);

        return $this->render('participation/new.html.twig', [
            'concours' => $concours,
            'participation' => $participation,
            'form' => $form->createView(),
            'oeuvres' => $oeuvres,
        ]);
    }

    #[Route('/{id<\d+>}', name: 'app_participation_show', methods: ['GET'])]
    public function show(
        Participation $participation,
        VoteRepository $voteRepository
    ): Response {
        // Compter les votes pour cette participation depuis la base de données
        $votesCount = $voteRepository->countVotesForParticipation($participation);
        
        return $this->render('participation/show.html.twig', [
            'participation' => $participation,
            'votesCount' => $votesCount,
        ]);
    }

    #[Route('/{id<\d+>}/edit', name: 'app_participation_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Participation $participation, 
        EntityManagerInterface $entityManager,
        UtilisateurRepository $userRepository,
        OeuvreRepository $oeuvreRepository
    ): Response {
        // Vérifier la session
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($session->get('user_id'));
        if (!$user || !in_array('ROLE_ARTISTE', $user->getRoles())) {
            $this->addFlash("error", "Vous devez être artiste pour modifier une participation.");
            return $this->redirectToRoute('app_participation_my');
        }

        $artiste = $user->getArtiste();
        if (!$artiste) {
            $this->addFlash("error", "Profil artiste introuvable.");
            return $this->redirectToRoute('app_participation_my');
        }

        // Vérifier que la participation appartient à l'artiste
        if (!$participation->getOeuvre() || $participation->getOeuvre()->getArtiste() !== $artiste) {
            $this->addFlash("error", "Vous n'avez pas le droit de modifier cette participation.");
            return $this->redirectToRoute('app_participation_my');
        }

        $form = $this->createForm(ParticipationEditType::class, $participation, [
            'artiste' => $artiste,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash("success", "Participation modifiée avec succès !");
            return $this->redirectToRoute('app_participation_my');
        }

        // Récupérer les œuvres de l'artiste pour l'affichage visuel
        $oeuvres = $oeuvreRepository->findByArtiste($artiste);

        return $this->render('participation/edit.html.twig', [
            'participation' => $participation,
            'form' => $form->createView(),
            'oeuvres' => $oeuvres,
        ]);
    }

    #[Route('/{id<\d+>}/edit-status', name: 'app_participation_edit_status', methods: ['GET', 'POST'])]
    public function editStatus(
        Request $request, 
        Participation $participation, 
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(ParticipationStatusEditType::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash("success", "Statut mis à jour avec succès !");
            return $this->redirectToRoute('app_participation_index');
        }

        return $this->render('participation/edit_status.html.twig', [
            'participation' => $participation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id<\d+>}', name: 'app_participation_delete', methods: ['POST'])]
    public function delete(Request $request, Participation $participation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$participation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($participation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_participation_index');
    }



#[Route('/mes-participations', name: 'app_participation_my', methods: ['GET'])]
public function myParticipations(
    Request $request,
    ParticipationRepository $participationRepository,
    UtilisateurRepository $userRepository,
    VoteRepository $voteRepository
): Response {
    // Vérifier la session
    $session = $request->getSession();
    if (!$session->has('user_id')) {
        return $this->redirectToRoute('app_login');
    }

    // Récupérer utilisateur connecté
    $user = $userRepository->find($session->get('user_id'));

    if (!$user || !in_array('ROLE_ARTISTE', $user->getRoles())) {
        $this->addFlash("error", "Vous devez être artiste pour accéder à vos participations.");
        return $this->redirectToRoute('app_participation_index');
    }

    $artiste = $user->getArtiste();
    if (!$artiste) {
        $this->addFlash("error", "Profil artiste introuvable.");
        return $this->redirectToRoute('app_participation_index');
    }

    // 🔥 Récupérer les participations via l'œuvre de l'artiste
    $participations = $participationRepository->findByArtiste($artiste);

    // Compter les votes pour chaque participation depuis la base de données
    $votesCount = [];
    foreach ($participations as $participation) {
        $votesCount[$participation->getId()] = $voteRepository->countVotesForParticipation($participation);
    }

    return $this->render('participation/my.html.twig', [
        'participations' => $participations,
        'votesCount' => $votesCount,
    ]);
}





























}
