<?php

namespace App\Controller;

use App\Entity\Enchere;
use App\Entity\Artiste;
use App\Entity\Oeuvre;
use App\Entity\Utilisateur;
use App\Enum\EnchereStatut;
use App\Form\EnchereType;
use App\Repository\ArtisteRepository;
use App\Repository\EnchereRepository;
use App\Repository\OeuvreRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/enchere')]
final class EnchereController extends AbstractController
{
    #[Route(name: 'app_enchere_index', methods: ['GET'])]
    public function index(
        Request $req,
        EnchereRepository $enchereRepository,
        ArtisteRepository $artisteRepository,
        OeuvreRepository $oeuvreRepository,
        UtilisateurRepository $userRepo
    ): Response {

        $user = $userRepo->find($req->getSession()->get('user_id'));

        if (!$user instanceof Utilisateur) {
            // not logged in
            return new JsonResponse(['message' => 'Not logged in'], Response::HTTP_FORBIDDEN);
        }

        if ($user->getRole() === 'ADMIN') {
            // admin user
            return $this->render('enchere/index.html.twig', [
                'encheres' => $enchereRepository->findAll(),
            ]);
        }

        if ($user->getRole() === 'ARTISTE') {
            $artiste = $artisteRepository->findOneBy(['user' => $user]);
            $artiste_enchere = $enchereRepository->findBy(['oeuvre' => $oeuvreRepository->findBy(['artiste' => $artiste])]);
            return $this->render('enchere/artist_index.html.twig', [
                'encheres' => $artiste_enchere,
            ]);
        }
        // regular user
        $openBets = $enchereRepository->findBy(
            ['Statut' => EnchereStatut::ACTIVE]
        );
        return $this->render('enchere/user_index.html.twig', [
            'encheres' => $openBets,
        ]);
    }

    #[Route('/search', name: 'app_enchere_search', methods: ['GET'])]
    public function search(Request $request, EnchereRepository $enchereRepository): JsonResponse
    {
        try {
            $query = $request->query->get('q', '');
            
            // Get all encheres and filter by artwork title
            $allEncheres = $enchereRepository->findAll();
            $filteredEncheres = [];
            
            foreach ($allEncheres as $enchere) {
                // Check if all relationships exist
                if (!$enchere->getOeuvre()) {
                    continue;
                }
                
                $oeuvre = $enchere->getOeuvre();
                $title = strtolower($oeuvre->getTitle());
                
                // Only add if matches search query
                if (empty($query) || strpos($title, strtolower($query)) !== false) {
                    // Get image path
                    $imagePath = $oeuvre->getImagePath();
                    $imageUrl = $imagePath ? '/uploads/oeuvres/' . $imagePath : '/assets/img/placeholder.jpg';
                    
                    $filteredEncheres[] = [
                        'id' => $enchere->getId(),
                        'prixDeBase' => $enchere->getPrixDeBase(),
                        'prixActuel' => $enchere->getPrixActuel(),
                        'dateDebut' => $enchere->getDateDebut()?->format('d/m/Y H:i'),
                        'dateFin' => $enchere->getDateFin()?->format('d/m/Y H:i'),
                        'oeuvreTitle' => $oeuvre->getTitle(),
                        'statut' => $enchere->getStatut()->value,
                        'imageUrl' => $imageUrl,
                        'showUrl' => $this->generateUrl('app_enchere_show', ['id' => $enchere->getId()]),
                    ];
                }
            }
            
            return new JsonResponse($filteredEncheres);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    #[Route('/new/choose_art', name: 'app_enchere_choose_art')]
    public function chooseArt(Request $request,
        ArtisteRepository $artisteRepository, 
        OeuvreRepository $oeuvreRepository): Response
    {
        $artiste = $artisteRepository->findOneBy(['user' => $request->getSession()->get('user_id')]);
        $oeuvres = $oeuvreRepository->findNotInEnchere()
            ->andWhere('o.artiste = :artiste')
            ->setParameter('artiste', $artiste)
            ->getQuery()
            ->getResult();

        return $this->render('enchere/choose_art.html.twig', [
            'oeuvres' => $oeuvres,
        ]);
    }

    #[Route('/new', name: 'app_enchere_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $enchere = new Enchere();
        $form = $this->createForm(EnchereType::class, $enchere);
        $enchere->setDateDebut(new \DateTime());
        $enchere->setStatut(EnchereStatut::ACTIVE);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $entityManager->persist($enchere);
            $enchere->setPrixActuel($enchere->getPrixDeBase());
            if ($form->isValid()) {
                $entityManager->flush();
                return $this->redirectToRoute('app_enchere_index', [], Response::HTTP_SEE_OTHER);
            }

        }

        return $this->render('enchere/new.html.twig', [
            'enchere' => $enchere,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_enchere_show', methods: ['GET'])]
    public function show(Enchere $enchere, Request $req, UtilisateurRepository $userRepo): Response
    {
        $user = $userRepo->find($req->getSession()->get('user_id'));

        if (!$user instanceof Utilisateur) {
            // not logged in
            return new JsonResponse(['message' => 'Not logged in'], Response::HTTP_FORBIDDEN);
        }
        if($user->getRole() === 'VISITEUR'){
            return $this->redirectToRoute('app_offre_new', 
            $req->query->all() + ['id' => $enchere->getId()]);
        }else if ($user->getRole() === 'ARTISTE'){
            return $this->render('enchere/show_front.html.twig', [
                'enchere' => $enchere,
            ]);
        }
        return $this->render('enchere/show.html.twig', [
            'enchere' => $enchere,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_enchere_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Enchere $enchere, EntityManagerInterface $entityManager,
     UtilisateurRepository $userRepo): Response
    {
        $user = $userRepo->find($request->getSession()->get('user_id'));

        if (!$user instanceof Utilisateur) {
            // not logged in
            return new JsonResponse(['message' => 'Not logged in'], Response::HTTP_FORBIDDEN);
        }
        $form = $this->createForm(EnchereType::class, $enchere);
        
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $entityManager->flush();

                return $this->redirectToRoute('app_enchere_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('enchere/edit.html.twig', [
            'enchere' => $enchere,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_enchere_delete', methods: ['POST'])]
    public function delete(Request $request, Enchere $enchere, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $enchere->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($enchere);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_enchere_index', [], Response::HTTP_SEE_OTHER);
    }
}
