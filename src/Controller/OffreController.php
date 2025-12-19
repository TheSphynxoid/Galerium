<?php

namespace App\Controller;

use App\Entity\Enchere;
use App\Entity\Offre;
use App\Entity\Utilisateur;
use App\Enum\EnchereStatut;
use App\Form\OffreType;
use App\Repository\OffreRepository;
use App\Repository\UtilisateurRepository;
use App\Service\RedisService;
use App\Service\AuctionUtils;
use App\Service\BiddingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/offre')]
final class OffreController extends AbstractController
{
    #[Route(name: 'app_offre_index', methods: ['GET'])]
    public function index(Request $request, OffreRepository $offreRepository, UtilisateurRepository $userRepo): Response
    {

        $user = $userRepo->find($request->getSession()->get('user_id'));


        if (!$user instanceof Utilisateur) {
            // not logged in
            return new JsonResponse(['message' => 'Not logged in'], Response::HTTP_FORBIDDEN);
        }

        return $this->render('offre/index.html.twig', [
            'offres' => $offreRepository->findBy(["User" => $user]),
        ]);
    }

    #[Route('/search', name: 'app_offre_search', methods: ['GET'])]
    public function search(Request $request, OffreRepository $offreRepository): JsonResponse
    {
        try {
            $query = $request->query->get('q', '');

            // Get all offers and filter by artwork title
            $allOffres = $offreRepository->findAll();
            $filteredOffres = [];

            foreach ($allOffres as $offre) {
                // Check if all relationships exist
                if (!$offre->getEchere() || !$offre->getEchere()->getOeuvre()) {
                    continue;
                }

                $oeuvre = $offre->getEchere()->getOeuvre();
                $title = strtolower($oeuvre->getTitle());

                // Only add if matches search query
                if (empty($query) || strpos($title, strtolower($query)) !== false) {
                    // Get image path - imagePath is the property that stores filename
                    $imagePath = $oeuvre->getImagePath();
                    $imageUrl = $imagePath ? '/uploads/oeuvres/' . $imagePath : '/assets/img/placeholder.jpg';

                    $filteredOffres[] = [
                        'id' => $offre->getId(),
                        'montant' => $offre->getMontant(),
                        'dateOffre' => $offre->getDateOffre()?->format('d/m/Y H:i'),
                        'oeuvreTitle' => $oeuvre->getTitle(),
                        'prixActuel' => $offre->getEchere()->getPrixActuel(),
                        'statut' => $offre->getEchere()->getStatut(),
                        'dateFin' => $offre->getEchere()->getDateFin()?->format('d/m/Y H:i'),
                        'imageUrl' => $imageUrl,
                        'showUrl' => $this->generateUrl('app_offre_show', ['id' => $offre->getId()]),
                        'editUrl' => $this->generateUrl('app_offre_edit', ['id' => $offre->getId()]),
                    ];
                }
            }

            return new JsonResponse($filteredOffres);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/new/{id<\d+>}', name: 'app_offre_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        Enchere $enchere,
        UtilisateurRepository $userRepo,
        EntityManagerInterface $entityManager,
        BiddingService $biddingService
    ): Response {
        $user = $userRepo->find($request->getSession()->get('user_id'));


        if (!$user instanceof Utilisateur) {
            // not logged in
            return new JsonResponse(['message' => 'Not logged in'], Response::HTTP_FORBIDDEN);
        }

        if ($enchere->getStatut() !== EnchereStatut::ACTIVE->value) {
            return new Response('Cette enchère est terminée.', Response::HTTP_FORBIDDEN);
        }

        $offre = new Offre();
        $offre->setDateOffre(new \DateTime());
        $offre->setEchere($enchere);
        $offre->setUser($user);

        $form = $this->createForm(OffreType::class, $offre);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            try {
                $this->ValidateOffer($form, $offre);
                if ($form->isValid()) {
                    $biddingService->placeBid($enchere, $user, (float)$offre->getMontant());
                    return $this->redirectToRoute('app_enchere_index', [], Response::HTTP_SEE_OTHER);
                }
            } catch (\Throwable $e) {
                $form->addError(new FormError($e->getMessage()));
            }
        }

        return $this->render('offre/new.html.twig', [
            'offre' => $offre,
            'enchere' => $enchere,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id<\d+>}', name: 'app_offre_show', methods: ['GET'])]
    public function show(Offre $offre): Response
    {
        return $this->render('offre/show.html.twig', [
            'offre' => $offre,
        ]);
    }

    #[Route('/{id<\d+>}/edit', name: 'app_offre_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Offre $offre, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OffreType::class, $offre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->ValidateOffer($form, $offre);
            if ($form->isValid()) {
                $entityManager->flush();
                return $this->redirectToRoute('app_offre_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('offre/edit.html.twig', [
            'offre' => $offre,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id<\d+>}', name: 'app_offre_delete', methods: ['POST'])]
    public function delete(Request $request, Offre $offre, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $offre->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($offre);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_offre_index', [], Response::HTTP_SEE_OTHER);
    }

    private function ValidateOffer(FormInterface $form, Offre $offre)
    {
        if ($offre->getEchere()->getStatut() !== EnchereStatut::ACTIVE->value) {
            $form->addError(new FormError('Vous ne pouvez pas faire une offre sur une enchère qui n\'est pas en cours.'));
            return false;
        }
        return true;
    }
}
