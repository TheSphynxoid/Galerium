<?php

namespace App\Controller;

use App\Entity\Participation;
use App\Form\ParticipationType;
use App\Form\ParticipationEditType;
use App\Repository\ParticipationRepository;
use App\Repository\ConcoursRepository;
use App\Repository\UtilisateurRepository;
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
        ConcoursRepository $concoursRepository
    ): Response {
        return $this->render('participation/index.html.twig', [
            'participations' => $participationRepository->findAll(),
            'concoursList' => $concoursRepository->findAll(),
        ]);
    }

    #[Route('/new/{concoursId}', name: 'app_participation_new', methods: ['GET', 'POST'])]
    public function new(
        int $concoursId,
        Request $request,
        EntityManagerInterface $entityManager,
        ConcoursRepository $concoursRepository,
        UtilisateurRepository $userRepository
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
            return $this->redirectToRoute('app_participation_index');
        }

        return $this->render('participation/new.html.twig', [
            'concours' => $concours,
            'participation' => $participation,
            'form' => $form->createView(),
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
        $form = $this->createForm(ParticipationEditType::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash("success", "Statut mis à jour avec succès !");
            return $this->redirectToRoute('app_participation_index');
        }

        return $this->render('participation/edit.html.twig', [
            'participation' => $participation,
            'form' => $form->createView(),
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
