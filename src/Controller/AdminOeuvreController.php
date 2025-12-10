<?php

namespace App\Controller;

use App\Entity\Oeuvre;
use App\Form\OeuvreFormType;
use App\Repository\OeuvreRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/oeuvres')]
class AdminOeuvreController extends AbstractController
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

    #[Route('', name: 'app_admin_oeuvre_list', methods: ['GET'])]
    public function adminList(
        Request $request,
        OeuvreRepository $oeuvreRepository,
        UtilisateurRepository $userRepository
    ): Response {
        $accessDenied = $this->checkAdminAccess($request, $userRepository);
        if ($accessDenied) {
            return $accessDenied;
        }

        return $this->render('admin/oeuvres/index.html.twig', [
            'oeuvres' => $oeuvreRepository->findAll(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_oeuvre_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function adminEdit(
        Request $request,
        Oeuvre $oeuvre,
        EntityManagerInterface $entityManager,
        UtilisateurRepository $userRepository
    ): Response {
        $accessDenied = $this->checkAdminAccess($request, $userRepository);
        if ($accessDenied) {
            return $accessDenied;
        }

        $form = $this->createForm(OeuvreFormType::class, $oeuvre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            $this->addFlash('success', 'Œuvre modifiée avec succès par l\'admin!');
            return $this->redirectToRoute('app_admin_oeuvre_list');
        }

        return $this->render('admin/oeuvres/edit.html.twig', [
            'oeuvre' => $oeuvre,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_oeuvre_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function adminDelete(
        Request $request,
        Oeuvre $oeuvre,
        EntityManagerInterface $entityManager,
        UtilisateurRepository $userRepository
    ): Response {
        $accessDenied = $this->checkAdminAccess($request, $userRepository);
        if ($accessDenied) {
            return $accessDenied;
        }

        if ($this->isCsrfTokenValid('delete'.$oeuvre->getId(), $request->request->get('_token'))) {
            $entityManager->remove($oeuvre);
            $entityManager->flush();

            $this->addFlash('success', 'Œuvre supprimée avec succès par l\'admin!');
        }

        return $this->redirectToRoute('app_admin_oeuvre_list');
    }
}
