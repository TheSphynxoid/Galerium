<?php

namespace App\Controller\Admin;

use App\Entity\Oeuvre;
use App\Repository\OeuvreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/oeuvres')]
class AdminOeuvreController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OeuvreRepository $oeuvreRepository
    ) {
    }

    #[Route('', name: 'admin_oeuvres_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $statut = $request->query->get('statut', '');
        $qb = $this->oeuvreRepository->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC');

        if ($statut) {
            $qb->andWhere('o.statut = :statut')
                ->setParameter('statut', $statut);
        }

        $oeuvres = $qb->getQuery()->getResult();

        return $this->render('admin/oeuvres/index.html.twig', [
            'oeuvres' => $oeuvres,
            'statut' => $statut,
        ]);
    }

    #[Route('/{id}', name: 'admin_oeuvres_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Oeuvre $oeuvre): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/oeuvres/show.html.twig', [
            'oeuvre' => $oeuvre,
        ]);
    }

    #[Route('/{id}/valider', name: 'admin_oeuvres_validate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function validate(Request $request, Oeuvre $oeuvre): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('validate_oeuvre' . $oeuvre->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_oeuvres_index');
        }

        $oeuvre->setStatut('publiee');
        $this->entityManager->flush();

        $this->addFlash('success', 'Œuvre validée et publiée avec succès !');

        return $this->redirectToRoute('admin_oeuvres_show', ['id' => $oeuvre->getId()]);
    }

    #[Route('/{id}/refuser', name: 'admin_oeuvres_reject', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function reject(Request $request, Oeuvre $oeuvre): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('reject_oeuvre' . $oeuvre->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_oeuvres_index');
        }

        $oeuvre->setStatut('refusee');
        $this->entityManager->flush();

        $this->addFlash('success', 'Œuvre refusée avec succès !');

        return $this->redirectToRoute('admin_oeuvres_show', ['id' => $oeuvre->getId()]);
    }

    #[Route('/{id}/masquer', name: 'admin_oeuvres_hide', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function hide(Request $request, Oeuvre $oeuvre): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('hide_oeuvre' . $oeuvre->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_oeuvres_index');
        }

        $oeuvre->setStatut('masquee');
        $this->entityManager->flush();

        $this->addFlash('success', 'Œuvre masquée avec succès !');

        return $this->redirectToRoute('admin_oeuvres_show', ['id' => $oeuvre->getId()]);
    }

    #[Route('/{id}/modifier-statut', name: 'admin_oeuvres_change_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function changeStatus(Request $request, Oeuvre $oeuvre): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('change_status' . $oeuvre->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_oeuvres_show', ['id' => $oeuvre->getId()]);
        }

        $newStatut = $request->request->get('statut');
        $allowedStatuts = ['en_attente', 'publiee', 'refusee', 'masquee'];

        if (!in_array($newStatut, $allowedStatuts)) {
            $this->addFlash('error', 'Statut invalide.');
            return $this->redirectToRoute('admin_oeuvres_show', ['id' => $oeuvre->getId()]);
        }

        $oeuvre->setStatut($newStatut);
        $this->entityManager->flush();

        $this->addFlash('success', 'Statut modifié avec succès !');

        return $this->redirectToRoute('admin_oeuvres_show', ['id' => $oeuvre->getId()]);
    }

    #[Route('/{id}/modifier-categorie', name: 'admin_oeuvres_change_category', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function changeCategory(Request $request, Oeuvre $oeuvre): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('change_category' . $oeuvre->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_oeuvres_show', ['id' => $oeuvre->getId()]);
        }

        $newCategorie = $request->request->get('categorie');
        $oeuvre->setCategorie($newCategorie);
        $this->entityManager->flush();

        $this->addFlash('success', 'Catégorie modifiée avec succès !');

        return $this->redirectToRoute('admin_oeuvres_show', ['id' => $oeuvre->getId()]);
    }

    #[Route('/{id}/supprimer', name: 'admin_oeuvres_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Oeuvre $oeuvre): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('delete_oeuvre' . $oeuvre->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_oeuvres_index');
        }

        // Supprimer l'image si elle existe
        if ($oeuvre->getImage()) {
            $imagePath = $this->getParameter('kernel.project_dir') . '/public/' . $oeuvre->getImage();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $this->entityManager->remove($oeuvre);
        $this->entityManager->flush();

        $this->addFlash('success', 'Œuvre supprimée avec succès !');

        return $this->redirectToRoute('admin_oeuvres_index');
    }
}



