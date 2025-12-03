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
final class UtilisateurController extends AbstractController
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route(name: 'app_utilisateur_index', methods: ['GET'])]
    public function index(Request $request, UtilisateurRepository $utilisateurRepository): Response
    {
        $search = $request->query->get('search', '');
        $roleFilter = $request->query->get('role', '');
        $sortBy = $request->query->get('sort', 'id');
        $sortOrder = $request->query->get('order', 'ASC');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $qb = $utilisateurRepository->searchAndFilter($search, $roleFilter, $sortBy, $sortOrder);
        
        $totalCount = count($qb->getQuery()->getResult());
        
        $qb->setFirstResult($offset)
           ->setMaxResults($limit);

        $utilisateurs = $qb->getQuery()->getResult();
        $totalPages = ceil($totalCount / $limit);

        return $this->render('utilisateur/index.html.twig', [
            'utilisateurs' => $utilisateurs,
            'search' => $search,
            'roleFilter' => $roleFilter,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
        ]);
    }

    #[Route('/new', name: 'app_utilisateur_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $utilisateur = new Utilisateur();
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($utilisateur->getPassword()) {
                $utilisateur->setPassword($this->passwordHasher->hashPassword($utilisateur, $utilisateur->getPassword()));
            }
            
            if (!$utilisateur->getDateInscription()) {
                $utilisateur->setDateInscription(new \DateTime());
            }
            
            $entityManager->persist($utilisateur);
            $entityManager->flush();

            return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('utilisateur/new.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form,
        ]);
    }

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
                $utilisateur->setPassword($this->passwordHasher->hashPassword($utilisateur, $plainPassword));
            }
            
            $em->flush();
            return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('utilisateur/edit.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_utilisateur_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, UtilisateurRepository $repo, EntityManagerInterface $em): Response
    {
        $utilisateur = $repo->find($id);
        if (!$utilisateur) {
            throw $this->createNotFoundException("Utilisateur introuvable");
        }

        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete'.$utilisateur->getId(), $token)) {
            $em->remove($utilisateur);
            $em->flush();
        }

        return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
    }
}
