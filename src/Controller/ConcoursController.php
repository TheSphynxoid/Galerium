<?php

namespace App\Controller;

use App\Entity\Concours;
use App\Repository\ConcoursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConcoursController extends AbstractController
{
    #[Route('/concours/test', name: 'app_concours_test')]
    public function test(ConcoursRepository $repository): Response
    {
        $concours = $repository->findAll();
        
        return $this->render('concours/test.html.twig', [
            'concours' => $concours,
        ]);
    }

    #[Route('/concours/creer', name: 'app_concours_creer', methods: ['GET', 'POST'])]
    public function creer(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $concours = new Concours();
            $concours->creerConcours(
                titre: $request->request->get('titre'),
                description: $request->request->get('description'),
                dateDebut: new \DateTime($request->request->get('dateDebut')),
                dateFin: new \DateTime($request->request->get('dateFin')),
                regles: $request->request->get('regles')
            );
            
            // Traiter le statut
            $statut = $request->request->get('statut');
            if ($statut) {
                // Normaliser le statut "cloture" en "clôturé"
                if ($statut === 'cloture') {
                    $statut = 'clôturé';
                }
                $concours->setStatut($statut);
            }
            
            // Traiter le vote public
            $votePublic = $request->request->get('votePublic');
            $concours->setVotePublic($votePublic === 'oui');
            
            $em->persist($concours);
            $em->flush();
            
            return $this->redirectToRoute('app_concours_test');
        }
        
        return $this->render('concours/creer.html.twig');
    }

    #[Route('/concours/{id}', name: 'app_concours_voir', methods: ['GET'])]
    public function voir(int $id, ConcoursRepository $repository): Response
    {
        $concours = $repository->find($id);
        
        if (!$concours) {
            throw $this->createNotFoundException('Concours non trouvé');
        }
        
        return $this->render('concours/voir.html.twig', [
            'concours' => $concours,
        ]);
    }

    #[Route('/concours/{id}/modifier', name: 'app_concours_modifier', methods: ['GET', 'POST'])]
    public function modifier(int $id, Request $request, ConcoursRepository $repository, EntityManagerInterface $em): Response
    {
        $concours = $repository->find($id);
        
        if (!$concours) {
            throw $this->createNotFoundException('Concours non trouvé');
        }
        
        if ($request->isMethod('POST')) {
            $concours->modifierConcours(
                titre: $request->request->get('titre') ?: null,
                description: $request->request->get('description') ?: null,
                dateDebut: $request->request->get('dateDebut') ? new \DateTime($request->request->get('dateDebut')) : null,
                dateFin: $request->request->get('dateFin') ? new \DateTime($request->request->get('dateFin')) : null,
                regles: $request->request->get('regles') ?: null
            );
            
            // Traiter le statut
            $statut = $request->request->get('statut');
            if ($statut) {
                // Normaliser le statut "cloture" en "clôturé"
                if ($statut === 'cloture') {
                    $statut = 'clôturé';
                }
                $concours->setStatut($statut);
            }
            
            // Traiter le vote public
            $votePublic = $request->request->get('votePublic');
            if ($votePublic !== null) {
                $concours->setVotePublic($votePublic === 'oui');
            }
            
            $em->flush();
            
            return $this->redirectToRoute('app_concours_test');
        }
        
        return $this->render('concours/modifier.html.twig', [
            'concours' => $concours,
        ]);
    }

    #[Route('/concours/{id}/cloturer', name: 'app_concours_cloturer', methods: ['POST'])]
    public function cloturer(int $id, ConcoursRepository $repository, EntityManagerInterface $em): Response
    {
        $concours = $repository->find($id);
        
        if (!$concours) {
            throw $this->createNotFoundException('Concours non trouvé');
        }
        
        $concours->cloturerConcours();
        $em->flush();
        
        return $this->redirectToRoute('app_concours_voir', ['id' => $id]);
    }

    #[Route('/concours/{id}/activer-vote', name: 'app_concours_activer_vote', methods: ['POST'])]
    public function activerVote(int $id, Request $request, ConcoursRepository $repository, EntityManagerInterface $em): Response
    {
        $concours = $repository->find($id);
        
        if (!$concours) {
            throw $this->createNotFoundException('Concours non trouvé');
        }
        
        $dateDebutVote = $request->request->get('dateDebutVote') 
            ? new \DateTime($request->request->get('dateDebutVote')) 
            : null;
        $dateFinVote = $request->request->get('dateFinVote') 
            ? new \DateTime($request->request->get('dateFinVote')) 
            : null;
        
        $concours->activeVotePublic($dateDebutVote, $dateFinVote);
        $em->flush();
        
        return $this->redirectToRoute('app_concours_voir', ['id' => $id]);
    }

    #[Route('/concours/{id}/supprimer', name: 'app_concours_supprimer', methods: ['POST'])]
    public function supprimer(int $id, ConcoursRepository $repository, EntityManagerInterface $em): Response
    {
        $concours = $repository->find($id);
        
        if (!$concours) {
            throw $this->createNotFoundException('Concours non trouvé');
        }
        
        $repository->remove($concours, true);
        
        $this->addFlash('success', 'Le concours "' . $concours->getTitre() . '" a été supprimé avec succès.');
        
        return $this->redirectToRoute('app_concours_test');
    }

    #[Route('/concours/api/test', name: 'app_concours_api_test')]
    public function apiTest(EntityManagerInterface $em, ConcoursRepository $repository): JsonResponse
    {
        // Test 1: Créer un concours
        $concours = new Concours();
        $concours->creerConcours(
            titre: 'Concours de Test',
            description: 'Description du concours de test',
            dateDebut: new \DateTime('2024-01-01'),
            dateFin: new \DateTime('2024-12-31'),
            regles: 'Règles du concours de test'
        );
        
        $em->persist($concours);
        $em->flush();
        
        $result = [
            'test1_creation' => [
                'id' => $concours->getId(),
                'titre' => $concours->getTitre(),
                'statut' => $concours->getStatut(),
                'votePublic' => $concours->isVotePublic(),
            ],
        ];
        
        // Test 2: Modifier le concours
        $concours->modifierConcours(
            titre: 'Concours de Test Modifié',
            description: 'Description modifiée'
        );
        $em->flush();
        
        $result['test2_modification'] = [
            'titre' => $concours->getTitre(),
            'description' => $concours->getDescription(),
        ];
        
        // Test 3: Activer le vote public
        $concours->activeVotePublic(
            new \DateTime('2024-06-01'),
            new \DateTime('2024-06-30')
        );
        $em->flush();
        
        $result['test3_vote_public'] = [
            'votePublic' => $concours->isVotePublic(),
            'dateDebutVote' => $concours->getDateDebutVote()?->format('Y-m-d H:i:s'),
            'dateFinVote' => $concours->getDateFinVote()?->format('Y-m-d H:i:s'),
        ];
        
        // Test 4: Clôturer le concours
        $concours->cloturerConcours();
        $em->flush();
        
        $result['test4_cloture'] = [
            'statut' => $concours->getStatut(),
        ];
        
        // Récupérer tous les concours
        $result['tous_les_concours'] = array_map(function($c) {
            return [
                'id' => $c->getId(),
                'titre' => $c->getTitre(),
                'statut' => $c->getStatut(),
                'votePublic' => $c->isVotePublic(),
            ];
        }, $repository->findAll());
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Tous les tests ont été exécutés avec succès',
            'tests' => $result,
        ]);
    }
}


