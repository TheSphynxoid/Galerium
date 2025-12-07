<?php

namespace App\Controller;

use App\Entity\Oeuvre;
use App\Form\OeuvreFormType;
use App\Repository\OeuvreRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class OeuvreController extends AbstractController
{
    #[Route('/artiste/oeuvres', name: 'app_oeuvre_index')]
    public function index(
        OeuvreRepository $oeuvreRepository,
        Request $request,
        UtilisateurRepository $userRepository
    ): Response {
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($session->get('user_id'));
        if (!$user || !in_array('ROLE_ARTISTE', $user->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        $artiste = $user->getArtiste();
        if (!$artiste) {
             return $this->redirectToRoute('app_artiste_profile');
        }

        $oeuvres = $oeuvreRepository->findByArtiste($artiste);

        return $this->render('oeuvre/index.html.twig', [
            'oeuvres' => $oeuvres,
        ]);
    }

    #[Route('/artiste/oeuvres/nouvelle', name: 'app_oeuvre_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UtilisateurRepository $userRepository
    ): Response {
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($session->get('user_id'));
        if (!$user || !in_array('ROLE_ARTISTE', $user->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        $artiste = $user->getArtiste();
        if (!$artiste) {
            return $this->redirectToRoute('app_artiste_profile');
        }

        $oeuvre = new Oeuvre();
        $oeuvre->setArtiste($artiste);

        $form = $this->createForm(OeuvreFormType::class, $oeuvre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // VichUploader handles the image automatically
            $entityManager->persist($oeuvre);
            $entityManager->flush();

            $this->addFlash('success', 'Œuvre ajoutée avec succès !');

            return $this->redirectToRoute('app_oeuvre_index');
        }

        return $this->render('oeuvre/new.html.twig', [
            'oeuvre' => $oeuvre,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/artiste/oeuvres/{id}', name: 'app_oeuvre_show', requirements: ['id' => '\d+'])]
    public function show(
        Oeuvre $oeuvre,
        Request $request,
        UtilisateurRepository $userRepository
    ): Response {
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('app_login');
        }
        $user = $userRepository->find($session->get('user_id'));
        
        // Vérifier que l'œuvre appartient à l'artiste connecté
        if (!$user || !$user->getArtiste() || $oeuvre->getArtiste() !== $user->getArtiste()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette œuvre.');
        }

        return $this->render('oeuvre/show.html.twig', [
            'oeuvre' => $oeuvre,
        ]);
    }

    #[Route('/artiste/oeuvres/{id}/modifier', name: 'app_oeuvre_edit', requirements: ['id' => '\d+'])]
    public function edit(
        Request $request,
        Oeuvre $oeuvre,
        EntityManagerInterface $entityManager,
        UtilisateurRepository $userRepository
    ): Response {
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('app_login');
        }
        $user = $userRepository->find($session->get('user_id'));

        // Vérifier que l'œuvre appartient à l'artiste connecté
        if (!$user || !$user->getArtiste() || $oeuvre->getArtiste() !== $user->getArtiste()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette œuvre.');
        }

        $form = $this->createForm(OeuvreFormType::class, $oeuvre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // VichUploader handles updates automatically
            $entityManager->flush();

            $this->addFlash('success', 'Œuvre modifiée avec succès !');

            return $this->redirectToRoute('app_oeuvre_index');
        }

        return $this->render('oeuvre/edit.html.twig', [
            'oeuvre' => $oeuvre,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/artiste/oeuvres/{id}/supprimer', name: 'app_oeuvre_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Request $request,
        Oeuvre $oeuvre,
        EntityManagerInterface $entityManager,
        UtilisateurRepository $userRepository
    ): Response {
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('app_login');
        }
        $user = $userRepository->find($session->get('user_id'));

        // Vérifier que l'œuvre appartient à l'artiste connecté
        if (!$user || !$user->getArtiste() || $oeuvre->getArtiste() !== $user->getArtiste()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette œuvre.');
        }

        if ($this->isCsrfTokenValid('delete' . $oeuvre->getId(), $request->request->get('_token'))) {
            // VichUploader handles file deletion on remove
            $entityManager->remove($oeuvre);
            $entityManager->flush();

            $this->addFlash('success', 'Œuvre supprimée avec succès !');
        }

        return $this->redirectToRoute('app_oeuvre_index');
    }

     // ... keep updateVote ...
    #[Route('/oeuvre/{id}/vote', name: 'app_oeuvre_increment_vote', requirements: ['id' => '\d+'], methods: ['POST'])]
     public function incrementVote(
         Request $request,
         Oeuvre $oeuvre,
         EntityManagerInterface $entityManager
     ): Response {
         // Endpoint public simplifié pour incrémenter les votes (ex: depuis la page publique)
         if (!$this->isCsrfTokenValid('vote' . $oeuvre->getId(), $request->request->get('_token'))) {
             return $this->json(['success' => false, 'message' => 'Token invalide'], 400);
         }
 
         $oeuvre->incrementVotes();
         $entityManager->flush();
 
         return $this->json(['success' => true, 'votes' => $oeuvre->getNbVotes()]);
     }

    #[Route('/artiste/oeuvres/statistiques', name: 'app_oeuvre_statistics')]
    public function statistics(
        OeuvreRepository $oeuvreRepository,
        Request $request,
        UtilisateurRepository $userRepository
    ): Response {
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($session->get('user_id'));
        if (!$user || !in_array('ROLE_ARTISTE', $user->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        $artiste = $user->getArtiste();
        $statistics = $oeuvreRepository->getStatisticsForArtiste($artiste);
        $oeuvres = $oeuvreRepository->findByArtiste($artiste);

        return $this->render('oeuvre/statistics.html.twig', [
            'statistics' => $statistics,
            'oeuvres' => $oeuvres,
        ]);
    }

    #[Route('/galerie', name: 'app_oeuvre_gallery')]
    public function gallery(OeuvreRepository $oeuvreRepository, Request $request): Response
    {
        $artisteName = $request->query->get('artiste');
        
        if ($artisteName) {
            // Filter by artist name if provided
            $oeuvres = $oeuvreRepository->createQueryBuilder('o')
                ->join('o.artiste', 'a')
                ->where('o.status = :status')
                ->andWhere('a.displayName LIKE :name')
                ->setParameter('status', Oeuvre::STATUS_PUBLIC)
                ->setParameter('name', '%' . $artisteName . '%')
                ->orderBy('o.createdAt', 'DESC')
                ->getQuery()
                ->getResult();
        } else {
            // Get all public artworks from all artists, ordered by newest first
            $oeuvres = $oeuvreRepository->findBy(
                ['status' => Oeuvre::STATUS_PUBLIC],
                ['createdAt' => 'DESC']
            );
        }

        return $this->render('oeuvre/gallery.html.twig', [
            'oeuvres' => $oeuvres,
        ]);
    }
}






