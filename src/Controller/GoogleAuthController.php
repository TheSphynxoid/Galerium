<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use League\OAuth2\Client\Provider\GoogleUser;

class GoogleAuthController extends AbstractController
{
    private EntityManagerInterface $em;
    private UtilisateurRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        EntityManagerInterface $em,
        UtilisateurRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/connect/google', name: 'connect_google')]
    public function connect(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect([], []);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheck(Request $request, ClientRegistry $clientRegistry): Response
    {
        $client = $clientRegistry->getClient('google');
        
        try {
            $googleUser = $client->fetchUser();
            
            $email = $googleUser->getEmail();
            $googleId = $googleUser->getId();
            $name = $googleUser->getName();
            $avatar = $googleUser->getAvatar();

            $nameParts = explode(' ', $name, 2);
            $prenom = $nameParts[0] ?? '';
            $nom = $nameParts[1] ?? '';

            $user = $this->userRepository->findOneBy(['email' => $email]);
            
            if (!$user) {
                $user = $this->userRepository->findOneBy(['googleId' => $googleId]);
            }

            if (!$user) {
                $user = new Utilisateur();
                $user->setEmail($email);
                $user->setNom($nom ?: 'User');
                $user->setPrenom($prenom ?: 'Google');
                
                // Si l'utilisateur a un avatar Google, il devient automatiquement ARTISTE
                // Sinon, il reste VISITEUR
                $user->setRole($avatar ? 'ARTISTE' : 'VISITEUR');

                $user->setDateInscription(new \DateTime());
                
                $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(32))));
            }

            $user->setGoogleId($googleId);
            if ($avatar) {
                $user->setAvatarUrl($avatar);
            }

            $this->em->persist($user);
            $this->em->flush();

            $session = $request->getSession();
            $session->set('user_id', $user->getId());
            $session->set('user_email', $user->getEmail());
            $session->set('user_role', $user->getRole());
            $session->set('user_name', $user->getFullName());

            return match (strtoupper($user->getRole())) {
                'JURY' => $this->redirectToRoute('app_jury'),
                'ARTISTE' => $this->redirectToRoute('app_artiste'),
                'VISITEUR' => $this->redirectToRoute('app_visiteur'),
                'ADMIN' => $this->redirectToRoute('app_admin'),
                default => $this->redirectToRoute('app_login'),
            };
        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
            return $this->redirectToRoute('app_login', ['error' => 'google_auth_failed', 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            error_log('Google OAuth Error: ' . $e->getMessage());
            return $this->redirectToRoute('app_login', ['error' => 'google_auth_failed']);
        }
    }
}

