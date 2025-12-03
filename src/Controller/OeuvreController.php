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
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, OeuvreService $oeuvreService): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $artiste = $user?->getArtiste();

        // Si l'utilisateur n'a pas de profil artiste, créer un profil vide
        if (!$artiste) {
            $artiste = (new Artiste())->setUser($user);
            // Définir les champs minimaux obligatoires
            $artiste->setDisplayName($user->getDisplayName() ?? $user->getEmail());
            $artiste->setSlug(strtolower(str_replace(' ', '-', $artiste->getDisplayName())));
            // Définir des valeurs par défaut pour éviter les erreurs de validation
            $artiste->setBiography('');
            $artiste->setSpecialty('');
        }

        $oeuvre = (new Oeuvre())->setArtiste($artiste);
        $form = $this->createForm(OeuvreType::class, $oeuvre);
        $form->handleRequest($request);

        error_log("=== FORM PROCESSING ===");
        error_log("Form submitted: " . ($form->isSubmitted() ? 'YES' : 'NO'));
        
        if ($form->isSubmitted()) {
            error_log("Form valid: " . ($form->isValid() ? 'YES' : 'NO'));
            
            if (!$form->isValid()) {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                error_log("Form errors: " . implode(', ', $errors));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            error_log("=== ATTEMPTING TO SAVE ===");
            $imageFile = $form->get('imageFile')->getData();

            try {
                $oeuvreService->save($oeuvre, $imageFile);
                error_log("=== SAVE COMPLETED ===");
                $this->addFlash('success', 'Œuvre ajoutée.');
            } catch (\Exception $e) {
                error_log("=== SAVE FAILED ===");
                error_log("Exception: " . $e->getMessage());
                error_log("Trace: " . $e->getTraceAsString());
                $this->addFlash('error', 'Erreur lors de l\'ajout de l\'œuvre: ' . $e->getMessage());
                return $this->render('oeuvre/form.html.twig', [
                    'form' => $form->createView(),
                    'title' => 'Ajouter une œuvre',
                ]);
            }

            return $this->redirectToRoute('app_artiste_dashboard');
        }

        return $this->render('oeuvre/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Ajouter une œuvre',
        ]);
    }

    #[Route('/{slug}/modifier', name: 'app_oeuvre_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(
        #[MapEntity(mapping: ['slug' => 'slug'])] Oeuvre $oeuvre,
        Request $request,
        OeuvreService $oeuvreService,
    ): Response {
        $this->denyAccessUnlessGranted('OEUVRE_EDIT', $oeuvre);

        $form = $this->createForm(OeuvreType::class, $oeuvre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            $oeuvreService->save($oeuvre, $imageFile);
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
    ): Response {
        if ($oeuvre->getStatus() !== Oeuvre::STATUS_PUBLIC) {
            $this->denyAccessUnlessGranted('OEUVRE_VIEW_PRIVATE', $oeuvre);
        }

        $oeuvre->incrementViews();
        $entityManager->flush();

        return $this->render('oeuvre/show.html.twig', [
            'oeuvre' => $oeuvre,
        ]);
    }

    #[Route('/{id}', name: 'app_oeuvre_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Oeuvre $oeuvre, Request $request, OeuvreService $oeuvreService): Response
    {
        $this->denyAccessUnlessGranted('OEUVRE_DELETE', $oeuvre);

        if ($this->isCsrfTokenValid('delete_oeuvre_'.$oeuvre->getId(), $request->request->get('_token'))) {
            $oeuvreService->delete($oeuvre);
            $this->addFlash('success', 'Œuvre supprimée.');
        }

        return $this->redirectToRoute('app_artiste_dashboard');
    }
}

