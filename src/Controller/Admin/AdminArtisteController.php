<?php

namespace App\Controller\Admin;

use App\Entity\Artiste;
use App\Form\AdminArtisteFormType;
use App\Repository\ArtisteRepository;
use App\Repository\OeuvreRepository;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/artistes')]
class AdminArtisteController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ArtisteRepository $artisteRepository,
        private OeuvreRepository $oeuvreRepository,
        private ParticipationRepository $participationRepository
    ) {
    }

    #[Route('', name: 'admin_artistes_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $search = $request->query->get('search', '');
        $artistes = $this->artisteRepository->createQueryBuilder('a')
            ->where('a.nom LIKE :search OR a.prenom LIKE :search OR a.email LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/artistes/index.html.twig', [
            'artistes' => $artistes,
            'search' => $search,
        ]);
    }

    #[Route('/nouveau', name: 'admin_artistes_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $artiste = new Artiste();
        $form = $this->createForm(AdminArtisteFormType::class, $artiste);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Encoder le mot de passe
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($artiste, $plainPassword);
                $artiste->setPassword($hashedPassword);
            }

            // Définir le rôle ROLE_ARTISTE
            $artiste->setRoles(['ROLE_ARTISTE']);

            // Gérer l'upload de la photo de profil
            $photoFile = $form->get('photoProfilFile')->getData();
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/artistes',
                        $newFilename
                    );
                    $artiste->setPhotoProfil('uploads/artistes/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo de profil.');
                }
            }

            $this->entityManager->persist($artiste);
            $this->entityManager->flush();

            $this->addFlash('success', 'Compte artiste créé avec succès !');

            return $this->redirectToRoute('admin_artistes_index');
        }

        return $this->render('admin/artistes/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_artistes_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Artiste $artiste): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $oeuvres = $this->oeuvreRepository->findBy(['artiste' => $artiste]);
        $participations = $this->participationRepository->findByArtiste($artiste);

        // Calculer les statistiques
        $totalVotes = array_sum(array_map(fn($o) => $o->getNbVotes(), $oeuvres));
        $totalCommentaires = array_sum(array_map(fn($o) => $o->getNbCommentaires(), $oeuvres));
        $totalParticipations = count($participations);

        return $this->render('admin/artistes/show.html.twig', [
            'artiste' => $artiste,
            'oeuvres' => $oeuvres,
            'participations' => $participations,
            'totalVotes' => $totalVotes,
            'totalCommentaires' => $totalCommentaires,
            'totalParticipations' => $totalParticipations,
        ]);
    }

    #[Route('/{id}/modifier', name: 'admin_artistes_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        Request $request,
        Artiste $artiste,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(AdminArtisteFormType::class, $artiste, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Changer le mot de passe si fourni
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($artiste, $plainPassword);
                $artiste->setPassword($hashedPassword);
            }

            // Les rôles sont gérés automatiquement par le formulaire

            // Gérer l'upload de la nouvelle photo de profil
            $photoFile = $form->get('photoProfilFile')->getData();
            if ($photoFile) {
                // Supprimer l'ancienne photo si elle existe
                if ($artiste->getPhotoProfil()) {
                    $oldPhotoPath = $this->getParameter('kernel.project_dir') . '/public/' . $artiste->getPhotoProfil();
                    if (file_exists($oldPhotoPath)) {
                        unlink($oldPhotoPath);
                    }
                }

                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/artistes',
                        $newFilename
                    );
                    $artiste->setPhotoProfil('uploads/artistes/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo de profil.');
                }
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Artiste mis à jour avec succès !');

            return $this->redirectToRoute('admin_artistes_show', ['id' => $artiste->getId()]);
        }

        return $this->render('admin/artistes/edit.html.twig', [
            'artiste' => $artiste,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/activer', name: 'admin_artistes_activate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function activate(Request $request, Artiste $artiste): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('activate_artiste' . $artiste->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_artistes_index');
        }

        $artiste->setIsActive(true);
        $this->entityManager->flush();

        $this->addFlash('success', 'Artiste activé avec succès !');

        return $this->redirectToRoute('admin_artistes_show', ['id' => $artiste->getId()]);
    }

    #[Route('/{id}/desactiver', name: 'admin_artistes_deactivate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deactivate(Request $request, Artiste $artiste): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('deactivate_artiste' . $artiste->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_artistes_index');
        }

        $artiste->setIsActive(false);
        $this->entityManager->flush();

        $this->addFlash('success', 'Artiste désactivé avec succès !');

        return $this->redirectToRoute('admin_artistes_show', ['id' => $artiste->getId()]);
    }

    #[Route('/{id}/supprimer', name: 'admin_artistes_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Artiste $artiste): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('delete_artiste' . $artiste->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_artistes_index');
        }

        // Supprimer toutes les œuvres de l'artiste
        foreach ($artiste->getOeuvres() as $oeuvre) {
            if ($oeuvre->getImage()) {
                $imagePath = $this->getParameter('kernel.project_dir') . '/public/' . $oeuvre->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
        }

        // Supprimer la photo de profil
        if ($artiste->getPhotoProfil()) {
            $photoPath = $this->getParameter('kernel.project_dir') . '/public/' . $artiste->getPhotoProfil();
            if (file_exists($photoPath)) {
                unlink($photoPath);
            }
        }

        // Supprimer les participations (cascade)
        $participations = $this->participationRepository->findByArtiste($artiste);
        foreach ($participations as $participation) {
            $this->entityManager->remove($participation);
        }

        $this->entityManager->remove($artiste);
        $this->entityManager->flush();

        $this->addFlash('success', 'Artiste et toutes ses données supprimés avec succès !');

        return $this->redirectToRoute('admin_artistes_index');
    }

    #[Route('/{id}/changer-role', name: 'admin_artistes_change_role', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function changeRole(Request $request, Artiste $artiste): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('change_role' . $artiste->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_artistes_show', ['id' => $artiste->getId()]);
        }

        $newRole = $request->request->get('role');
        $allowedRoles = ['ROLE_ARTISTE', 'ROLE_USER', 'ROLE_ADMIN'];

        if (!in_array($newRole, $allowedRoles)) {
            $this->addFlash('error', 'Rôle invalide.');
            return $this->redirectToRoute('admin_artistes_show', ['id' => $artiste->getId()]);
        }

        $artiste->setRoles([$newRole]);
        $this->entityManager->flush();

        $this->addFlash('success', 'Rôle modifié avec succès !');

        return $this->redirectToRoute('admin_artistes_show', ['id' => $artiste->getId()]);
    }
}

