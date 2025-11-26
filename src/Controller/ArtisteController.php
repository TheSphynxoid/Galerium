<?php

namespace App\Controller;

use App\Entity\Artiste;
use App\Form\ArtisteProfileFormType;
use App\Form\ArtisteRegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\String\Slugger\SluggerInterface;

class ArtisteController extends AbstractController
{
    #[Route('/artiste/{id}', name: 'app_artiste_public_profile', requirements: ['id' => '\d+'])]
    public function publicProfile(Artiste $artiste): Response
    {
        // Page publique "Profil artiste" (mini portfolio)
        return $this->render('artiste/public_profile.html.twig', [
            'artiste' => $artiste,
        ]);
    }

    #[Route('/artiste/inscription', name: 'app_artiste_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_artiste_profile');
        }

        $artiste = new Artiste();
        $form = $this->createForm(ArtisteRegistrationFormType::class, $artiste);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Encoder le mot de passe
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($artiste, $plainPassword);
            $artiste->setPassword($hashedPassword);

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

            $entityManager->persist($artiste);
            $entityManager->flush();

            $this->addFlash('success', 'Inscription réussie ! Vous pouvez maintenant vous connecter.');

            return $this->redirectToRoute('app_artiste_login');
        }

        return $this->render('artiste/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/artiste/connexion', name: 'app_artiste_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_artiste_profile');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('artiste/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/artiste/deconnexion', name: 'app_artiste_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/artiste/profile', name: 'app_artiste_profile_alias')]
    public function profileAlias(): Response
    {
        return $this->redirectToRoute('app_artiste_profile');
    }

    #[Route('/artiste/profil', name: 'app_artiste_profile')]
    public function profile(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ARTISTE');

        /** @var Artiste $artiste */
        $artiste = $this->getUser();

        $form = $this->createForm(ArtisteProfileFormType::class, $artiste);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

            $entityManager->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès !');

            return $this->redirectToRoute('app_artiste_profile');
        }

        return $this->render('artiste/profile.html.twig', [
            'artiste' => $artiste,
            'profileForm' => $form,
        ]);
    }

    #[Route('/artiste/supprimer', name: 'app_artiste_delete', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ARTISTE');

        /** @var Artiste $artiste */
        $artiste = $this->getUser();

        if (!$this->isCsrfTokenValid('delete_artiste' . $artiste->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_artiste_profile');
        }

        // Déconnexion logique: laisser le firewall gérer après suppression
        $entityManager->remove($artiste);
        $entityManager->flush();

        $this->addFlash('success', 'Votre compte a été supprimé.');
        return $this->redirectToRoute('app_artiste_login');
    }
}




