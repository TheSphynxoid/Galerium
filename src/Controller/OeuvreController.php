<?php

namespace App\Controller;

use App\Entity\Oeuvre;
use App\Form\OeuvreFormType;
use App\Repository\OeuvreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class OeuvreController extends AbstractController
{
    #[Route('/artiste/oeuvres', name: 'app_oeuvre_index')]
    public function index(OeuvreRepository $oeuvreRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ARTISTE');

        /** @var \App\Entity\Artiste $artiste */
        $artiste = $this->getUser();
        $oeuvres = $oeuvreRepository->findByArtiste($artiste);

        return $this->render('oeuvre/index.html.twig', [
            'oeuvres' => $oeuvres,
        ]);
    }

    #[Route('/artiste/oeuvres/nouvelle', name: 'app_oeuvre_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ARTISTE');

        $oeuvre = new Oeuvre();
        /** @var \App\Entity\Artiste $artiste */
        $artiste = $this->getUser();
        $oeuvre->setArtiste($artiste);

        $form = $this->createForm(OeuvreFormType::class, $oeuvre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload de l'image
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/oeuvres',
                        $newFilename
                    );
                    $oeuvre->setImage('uploads/oeuvres/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                }
            }

            $entityManager->persist($oeuvre);
            $entityManager->flush();

            $this->addFlash('success', 'Œuvre ajoutée avec succès !');

            return $this->redirectToRoute('app_oeuvre_index');
        }

        return $this->render('oeuvre/new.html.twig', [
            'oeuvre' => $oeuvre,
            'form' => $form,
        ]);
    }

    #[Route('/artiste/oeuvres/{id}', name: 'app_oeuvre_show', requirements: ['id' => '\d+'])]
    public function show(Oeuvre $oeuvre): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ARTISTE');

        // Vérifier que l'œuvre appartient à l'artiste connecté
        if ($oeuvre->getArtiste() !== $this->getUser()) {
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
        SluggerInterface $slugger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ARTISTE');

        // Vérifier que l'œuvre appartient à l'artiste connecté
        if ($oeuvre->getArtiste() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette œuvre.');
        }

        $form = $this->createForm(OeuvreFormType::class, $oeuvre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload de la nouvelle image
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                // Supprimer l'ancienne image si elle existe
                if ($oeuvre->getImage()) {
                    $oldImagePath = $this->getParameter('kernel.project_dir') . '/public/' . $oeuvre->getImage();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/oeuvres',
                        $newFilename
                    );
                    $oeuvre->setImage('uploads/oeuvres/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Œuvre modifiée avec succès !');

            return $this->redirectToRoute('app_oeuvre_index');
        }

        return $this->render('oeuvre/edit.html.twig', [
            'oeuvre' => $oeuvre,
            'form' => $form,
        ]);
    }

    #[Route('/artiste/oeuvres/{id}/supprimer', name: 'app_oeuvre_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Request $request,
        Oeuvre $oeuvre,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ARTISTE');

        // Vérifier que l'œuvre appartient à l'artiste connecté
        if ($oeuvre->getArtiste() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette œuvre.');
        }

        if ($this->isCsrfTokenValid('delete' . $oeuvre->getId(), $request->request->get('_token'))) {
            // Supprimer l'image associée
            if ($oeuvre->getImage()) {
                $imagePath = $this->getParameter('kernel.project_dir') . '/public/' . $oeuvre->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $entityManager->remove($oeuvre);
            $entityManager->flush();

            $this->addFlash('success', 'Œuvre supprimée avec succès !');
        }

        return $this->redirectToRoute('app_oeuvre_index');
    }

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
    public function statistics(OeuvreRepository $oeuvreRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ARTISTE');

        /** @var \App\Entity\Artiste $artiste */
        $artiste = $this->getUser();
        $statistics = $oeuvreRepository->getStatisticsForArtiste($artiste);
        $oeuvres = $oeuvreRepository->findByArtiste($artiste);

        return $this->render('oeuvre/statistics.html.twig', [
            'statistics' => $statistics,
            'oeuvres' => $oeuvres,
        ]);
    }
}




