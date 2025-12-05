<?php

namespace App\Controller;

use App\Entity\Artiste;
use App\Entity\Oeuvre;
use App\Form\OeuvreType;
use App\Repository\CategorieRepository;
use App\Repository\OeuvreRepository;
use App\Service\OeuvreService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/oeuvres')]
class OeuvreController extends AbstractController
{
    #[Route('', name: 'app_oeuvre_index', methods: ['GET'])]
    public function index(Request $request, OeuvreRepository $repository, CategorieRepository $categorieRepository): Response
    {
        $category = $request->query->get('categorie');
        $term = $request->query->get('q');

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        $artiste = $user?->getArtiste();

        if ($artiste) {
            // Affiche toutes les œuvres de l'artiste connecté avec filtres
            $oeuvres = $repository->findByArtiste($artiste, null, $term, $category);
        } else {
            // Visiteurs : galerie publique
            $oeuvres = $repository->search($term, $category, Oeuvre::STATUS_PUBLIC);
        }

        return $this->render('oeuvre/index.html.twig', [
            'oeuvres' => $oeuvres,
            'categories' => $categorieRepository->findAll(),
        ]);
    }

    #[Route('/nouvelle', name: 'app_oeuvre_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        OeuvreService $oeuvreService,
        \App\Repository\UtilisateurRepository $userRepository
    ): Response {
        // Utiliser l'authentification par session comme dans ArtisteController
        $session = $request->getSession();

        if (!$session->has('user_id')) {
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($session->get('user_id'));

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $artiste = $user->getArtiste();

        // Si l'utilisateur n'a pas de profil artiste, rediriger vers la création de profil
        if (!$artiste) {
            $this->addFlash('warning', 'Veuillez d\'abord compléter votre profil artiste.');
            return $this->redirectToRoute('app_artiste_profile');
        }

        $oeuvre = (new Oeuvre())->setArtiste($artiste);
        $form = $this->createForm(OeuvreType::class, $oeuvre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // VichUploader gère automatiquement l'upload via le formulaire
                $oeuvreService->save($oeuvre);
                $this->addFlash('success', 'Œuvre ajoutée avec succès!');
                return $this->redirectToRoute('app_artiste_dashboard');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'enregistrement: ' . $e->getMessage());
            }
        }

        return $this->render('oeuvre/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Ajouter une œuvre',
        ]);
    }

    #[Route('/{slug}/modifier', name: 'app_oeuvre_edit', methods: ['GET', 'POST'])]
    public function edit(
        #[MapEntity(mapping: ['slug' => 'slug'])] Oeuvre $oeuvre,
        Request $request,
        OeuvreService $oeuvreService,
        \App\Repository\UtilisateurRepository $userRepository
    ): Response {
        // Vérifier l'authentification par session
        $session = $request->getSession();

        if (!$session->has('user_id')) {
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($session->get('user_id'));

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $artiste = $user->getArtiste();

        if (!$artiste) {
            $this->addFlash('error', 'Vous devez avoir un profil artiste pour modifier une œuvre.');
            return $this->redirectToRoute('app_artiste_profile');
        }

        // Vérifier que l'artiste est bien le propriétaire de l'œuvre
        if ($oeuvre->getArtiste()->getId() !== $artiste->getId()) {
            $this->addFlash('error', 'Vous n\'avez pas l\'autorisation de modifier cette œuvre.');
            return $this->redirectToRoute('app_artiste_dashboard');
        }

        $form = $this->createForm(OeuvreType::class, $oeuvre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // VichUploader gère automatiquement l'upload via le formulaire
            $oeuvreService->save($oeuvre);
            $this->addFlash('success', 'Œuvre mise à jour.');

            return $this->redirectToRoute('app_artiste_dashboard');
        }

        return $this->render('oeuvre/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Modifier une œuvre',
        ]);
    }

    #[Route('/{slug}', name: 'app_oeuvre_show', methods: ['GET'])]
    public function show(
        #[MapEntity(mapping: ['slug' => 'slug'])] Oeuvre $oeuvre,
        \Doctrine\ORM\EntityManagerInterface $entityManager,
        Request $request,
        \App\Repository\UtilisateurRepository $userRepository
    ): Response {
        if ($oeuvre->getStatus() !== Oeuvre::STATUS_PUBLIC) {
            // Vérification manuelle pour les œuvres privées
            $session = $request->getSession();
            $hasAccess = false;

            if ($session->has('user_id')) {
                $user = $userRepository->find($session->get('user_id'));
                if ($user) {
                    $artiste = $user->getArtiste();
                    // L'artiste propriétaire peut voir ses œuvres privées
                    if ($artiste && $oeuvre->getArtiste()->getId() === $artiste->getId()) {
                        $hasAccess = true;
                    }
                }
            }

            if (!$hasAccess) {
                $this->addFlash('error', 'Cette œuvre est privée.');
                return $this->redirectToRoute('app_oeuvre_index');
            }
        }

        $oeuvre->incrementViews();
        $entityManager->flush();

        return $this->render('oeuvre/show.html.twig', [
            'oeuvre' => $oeuvre,
        ]);
    }

    #[Route('/{id}', name: 'app_oeuvre_delete', methods: ['POST'])]
    public function delete(
        Oeuvre $oeuvre, 
        Request $request, 
        OeuvreService $oeuvreService,
        \App\Repository\UtilisateurRepository $userRepository
    ): Response {
        // Vérifier l'authentification par session
        $session = $request->getSession();

        if (!$session->has('user_id')) {
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($session->get('user_id'));

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $artiste = $user->getArtiste();

        if (!$artiste) {
            $this->addFlash('error', 'Vous devez avoir un profil artiste.');
            return $this->redirectToRoute('app_artiste_profile');
        }

        // Vérifier que l'artiste est bien le propriétaire de l'œuvre
        if ($oeuvre->getArtiste()->getId() !== $artiste->getId()) {
            $this->addFlash('error', 'Vous n\'avez pas l\'autorisation de supprimer cette œuvre.');
            return $this->redirectToRoute('app_artiste_dashboard');
        }

        if ($this->isCsrfTokenValid('delete_oeuvre_'.$oeuvre->getId(), $request->request->get('_token'))) {
            $oeuvreService->delete($oeuvre);
            $this->addFlash('success', 'Œuvre supprimée.');
        }

        return $this->redirectToRoute('app_artiste_dashboard');
    }
}
