<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/utilisateur')]
class UtilisateurController extends AbstractController
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    
    // Liste des utilisateurs
   
    #[Route(name: 'app_utilisateur_index', methods: ['GET'])]
    public function index(Request $request, UtilisateurRepository $repo): Response
    {
        $search = $request->query->get('search', '');
        $roleFilter = $request->query->get('role', '');
        $sortBy = $request->query->get('sort', 'id');
        $sortOrder = $request->query->get('order', 'ASC');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $qb = $repo->searchAndFilter($search, $roleFilter, $sortBy, $sortOrder);

        $totalCount = count($qb->getQuery()->getResult());

        $qb->setFirstResult($offset)->setMaxResults($limit);

        $utilisateurs = $qb->getQuery()->getResult();

        if ($request->isXmlHttpRequest()) {
            return $this->render('utilisateur/_users_table.html.twig', [
                'utilisateurs' => $utilisateurs
            ]);
        }

        return $this->render('utilisateur/index.html.twig', [
            'utilisateurs' => $utilisateurs,
            'search' => $search,
            'roleFilter' => $roleFilter,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'currentPage' => $page,
            'totalPages' => ceil($totalCount / $limit),
            'totalCount' => $totalCount,
        ]);
    }

    
    // Recherche AJAX
    
    #[Route('/search-ajax', name: 'app_utilisateur_search_ajax', methods: ['GET'])]
    public function searchAjax(Request $request, UtilisateurRepository $repo): Response
    {
        $search = $request->query->get('search', '');
        $roleFilter = $request->query->get('role', '');
        $sortBy = $request->query->get('sort', 'id');
        $sortOrder = $request->query->get('order', 'ASC');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));
        $offset = ($page - 1) * $limit;

        $qb = $repo->searchAndFilter($search, $roleFilter, $sortBy, $sortOrder);

        $totalCount = (int) count($qb->getQuery()->getResult());

        $qb->setFirstResult($offset)->setMaxResults($limit);
        $utilisateurs = $qb->getQuery()->getResult();

        return $this->render('utilisateur/_users_table.html.twig', [
            'utilisateurs' => $utilisateurs,
            'currentPage' => $page,
            'totalPages' => (int) ceil($totalCount / $limit),
            'totalCount' => $totalCount,
            'limit' => $limit,
        ]);
    }

    
    // Nouveau utilisateur
    
    #[Route('/new', name: 'app_utilisateur_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $utilisateur = new Utilisateur();
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($utilisateur->getPassword()) {
                $utilisateur->setPassword(
                    $this->passwordHasher->hashPassword($utilisateur, $utilisateur->getPassword())
                );
            }

            if (!$utilisateur->getDateInscription()) {
                $utilisateur->setDateInscription(new \DateTime());
            }

            $em->persist($utilisateur);
            $em->flush();

            return $this->redirectToRoute('app_utilisateur_index');
        }

        return $this->render('utilisateur/new.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form,
        ]);
    }

    
    // Voir utilisateur
    
    #[Route('/{id}', name: 'app_utilisateur_show', methods: ['GET'])]
    public function show(int $id, UtilisateurRepository $repo): Response
    {
        $utilisateur = $repo->find($id);
        if (!$utilisateur) {
            throw $this->createNotFoundException("Utilisateur introuvable");
        }

        return $this->render('utilisateur/show.html.twig', [
            'utilisateur' => $utilisateur,
        ]);
    }

    
    // Modifier utilisateur

    #[Route('/{id}/edit', name: 'app_utilisateur_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, UtilisateurRepository $repo, EntityManagerInterface $em): Response
    {
        $utilisateur = $repo->find($id);
        if (!$utilisateur) {
            throw $this->createNotFoundException("Utilisateur introuvable");
        }

        $form = $this->createForm(UtilisateurType::class, $utilisateur, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('password')->getData();
            if (!empty($plainPassword)) {
                $utilisateur->setPassword(
                    $this->passwordHasher->hashPassword($utilisateur, $plainPassword)
                );
            }

            $em->flush();
            return $this->redirectToRoute('app_utilisateur_index');
        }

        return $this->render('utilisateur/edit.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form,
        ]);
    }

    
    // Supprimer utilisateur
    
    #[Route('/{id}/delete', name: 'app_utilisateur_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, UtilisateurRepository $repo, EntityManagerInterface $em): Response
    {
        $utilisateur = $repo->find($id);
        if (!$utilisateur) {
            throw $this->createNotFoundException("Utilisateur introuvable");
        }

        if ($this->isCsrfTokenValid('delete'.$utilisateur->getId(), $request->request->get('_token'))) {
            $em->remove($utilisateur);
            $em->flush();
        }

        return $this->redirectToRoute('app_utilisateur_index');
    }
}
