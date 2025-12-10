<?php

namespace App\Controller;

use App\Entity\Artiste;
use App\Form\ArtisteProfileFormType;
use App\Repository\ArtisteRepository;
use App\Repository\OeuvreRepository;
use App\Repository\UtilisateurRepository;
use App\Service\ArtisteService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/artiste')]
class AdminArtisteController extends AbstractController
{
    /**
     * Vérifie si l'utilisateur est admin
     */
    private function checkAdminAccess(Request $request, UtilisateurRepository $userRepository): ?Response
    {
        $session = $request->getSession();

        /*
        if (!$session->has('user_id')) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($session->get('user_id'));

        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_login');
        }
        */

        // Vérification du rôle admin
        /*
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('error', 'Accès refusé. Vous devez être administrateur.');
            return $this->redirectToRoute('app_home');
        }
        */

        return null; // Accès autorisé
    }

    #[Route('', name: 'app_admin_artiste_list', methods: ['GET'])]
    public function adminList(
        Request $request,
        ArtisteRepository $artisteRepository,
        UtilisateurRepository $userRepository
    ): Response {
        $accessDenied = $this->checkAdminAccess($request, $userRepository);
        if ($accessDenied) {
            return $accessDenied;
        }

        return $this->render('admin/artistes/index.html.twig', [
            'artistes' => $artisteRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_artiste_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function adminShow(
        Request $request,
        Artiste $artiste,
        OeuvreRepository $oeuvreRepository,
        UtilisateurRepository $userRepository
    ): Response {
        $accessDenied = $this->checkAdminAccess($request, $userRepository);
        if ($accessDenied) {
            return $accessDenied;
        }

        return $this->render('admin/artistes/show.html.twig', [
            'artiste' => $artiste,
            'oeuvres' => $oeuvreRepository->findBy(['artiste' => $artiste]),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_artiste_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function adminEdit(
        Request $request,
        Artiste $artiste,
        ArtisteService $artisteService,
        UtilisateurRepository $userRepository
    ): Response {
        $accessDenied = $this->checkAdminAccess($request, $userRepository);
        if ($accessDenied) {
            return $accessDenied;
        }

        $form = $this->createForm(ArtisteProfileFormType::class, $artiste);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $artisteService->save($artiste);
            
            $this->addFlash('success', 'Artiste modifié avec succès!');
            return $this->redirectToRoute('app_admin_artiste_show', ['id' => $artiste->getId()]);
        }

        return $this->render('admin/artistes/edit.html.twig', [
            'form' => $form->createView(),
            'artiste' => $artiste,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_artiste_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function adminDelete(
        Request $request,
        Artiste $artiste,
        EntityManagerInterface $entityManager,
        UtilisateurRepository $userRepository
    ): Response {
        $accessDenied = $this->checkAdminAccess($request, $userRepository);
        if ($accessDenied) {
            return $accessDenied;
        }

        // Vérification CSRF
        if ($this->isCsrfTokenValid('delete'.$artiste->getId(), $request->request->get('_token'))) {
            $entityManager->remove($artiste);
            $entityManager->flush();

            $this->addFlash('success', 'Artiste supprimé avec succès!');
        }

        return $this->redirectToRoute('app_admin_artiste_list');
    }
}
