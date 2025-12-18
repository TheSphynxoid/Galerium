<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    private EntityManagerInterface $em;
    private UtilisateurRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private JWTTokenManagerInterface $jwtManager;

    public function __construct(
        EntityManagerInterface $em,
        UtilisateurRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ) {
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->jwtManager = $jwtManager;
    }

    #[Route('/', name: 'app_home')]
    public function home(Request $request): Response
    {
        $session = $request->getSession();
        if ($session->has('user_id')) {
            return $this->redirectToRoleRoute($session->get('user_role'));
        }
        return $this->redirectToRoute('app_login');
    }

    #[Route('/auth', name: 'app_auth')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_login');
    }

    #[Route('/auth/registre', name: 'app_registre', methods: ['GET','POST'])]
    public function registre(Request $request): Response
    {
        $error = null;

        if ($request->isMethod('POST')) {
            $nom = trim($request->request->get('nom', ''));
            $prenom = trim($request->request->get('prenom', ''));
            $email = strtolower(trim($request->request->get('email', '')));
            $password = trim($request->request->get('password', ''));
            $role = strtoupper(trim($request->request->get('role', 'VISITEUR')));

            if (!in_array($role, ['VISITEUR', 'ARTISTE', 'JURY'])) {
                $role = 'VISITEUR';
            }

            if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
                $error = 'Tous les champs sont obligatoires.';
            } elseif ($this->userRepository->findOneBy(['email' => $email])) {
                $error = 'Cet email est déjà utilisé.';
            } else {
                $user = new Utilisateur();
                $user->setNom($nom);
                $user->setPrenom($prenom);
                $user->setEmail($email);
                $user->setPassword($this->passwordHasher->hashPassword($user, $password));
                $user->setRole($role);
                $user->setDateInscription(new \DateTime());
                $user->setIsActive(true); // par défaut actif

                $this->em->persist($user);
                $this->em->flush();

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('auth/registre.html.twig', [
            'error' => $error
        ]);
    }

    #[Route('/auth/login', name: 'app_login', methods: ['GET','POST'])]
    public function login(Request $request): Response
    {
        $session = $request->getSession();
        if ($session->has('user_id')) {
            return $this->redirectToRoleRoute($session->get('user_role'));
        }

        $error = null;
        $email = '';

        // LOGIN JSON / API
        if ($request->isMethod('POST') &&
            ($request->getContentTypeFormat() === 'json' || str_contains($request->headers->get('Content-Type', ''), 'application/json'))) {

            $data = json_decode($request->getContent(), true);
            $email = strtolower(trim($data['email'] ?? ''));
            $password = $data['password'] ?? '';

            $user = $this->userRepository->findOneBy(['email' => $email]);

            if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
                return new JsonResponse(['error' => 'Email ou mot de passe invalide'], Response::HTTP_UNAUTHORIZED);
            }

            if ($user->isSignaled()) {
                return new JsonResponse([
                    'error' => 'Votre compte a été signalé et vous ne pouvez pas vous connecter'
                ], Response::HTTP_FORBIDDEN);
            }

            $token = $this->jwtManager->create($user);

            return new JsonResponse([
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole(),
                ]
            ]);
        }

        // LOGIN FORM
        if ($request->isMethod('POST')) {
            $email = strtolower(trim($request->request->get('email', '')));
            $password = trim($request->request->get('password', ''));

            if (empty($email) || empty($password)) {
                $error = 'Veuillez saisir email et mot de passe.';
            } else {
                $user = $this->userRepository->findOneBy(['email' => $email]);
                if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
                    $error = 'Email ou mot de passe invalide.';
                } elseif ($user->isSignaled()) {
                    $error = 'Votre compte a été signalé et vous ne pouvez pas vous connecter.';
                } else {
                    $token = $this->jwtManager->create($user);
                    $session->set('user_id', $user->getId());
                    $session->set('user_email', $user->getEmail());
                    $session->set('user_role', $user->getRole());
                    $session->set('user_name', $user->getFullName());
                    $session->set('jwt_token', $token);

                    return $this->redirectToRoleRoute($user->getRole());
                }
            }
        }

        return $this->render('auth/login.html.twig', [
            'email' => $email,
            'error' => $error
        ]);
    }

    #[Route('/auth/logout', name: 'app_logout')]
    public function logout(Request $request): Response
    {
        $request->getSession()->clear();
        return $this->redirectToRoute('app_login');
    }

    #[Route('/auth/me', name: 'app_me', methods: ['GET'])]
    public function me(Request $request): JsonResponse
    {
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $this->userRepository->find($session->get('user_id'));
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'role' => $user->getRole(),
            'isSignaled' => $user->isSignaled()
        ]);
    }

    private function redirectToRoleRoute(string $role): Response
    {
        return match (strtoupper($role)) {
            'JURY' => $this->redirectToRoute('app_jury'),
            'ARTISTE' => $this->redirectToRoute('app_artiste_profile'),
            'VISITEUR' => $this->redirectToRoute('app_visiteur'),
            'ADMIN', 'ROLE_ADMIN' => $this->redirectToRoute('app_admin'),
            default => $this->redirectToRoute('app_login'),
        };
    }

    #[Route('/create-test-user', name: 'app_create_test_user')]
    public function createTestUser(): Response
    {
        $user = new Utilisateur();
        $user->setNom('Doe');
        $user->setPrenom('John');
        $user->setEmail('admin@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'admin123'));
        $user->setRole('ROLE_ADMIN');
        $user->setIsActive(true);
        $user->setDateInscription(new \DateTime());

        $this->em->persist($user);
        $this->em->flush();

        return new Response('Utilisateur test créé : admin@example.com / admin123 (Role: ROLE_ADMIN)');
    }

    // Route admin pour signaler un utilisateur
    #[Route('/admin/signal-user/{id}', name: 'app_signal_user')]
    public function signalUser(Utilisateur $user): Response
    {
        $user->setIsSignaled(true);
        $this->em->flush();

        return new Response("Utilisateur {$user->getEmail()} signalé avec succès.");
    }

    // Route admin pour désignaler un utilisateur
    #[Route('/admin/un-signal-user/{id}', name: 'app_unsignal_user')]
    public function unsignalUser(Utilisateur $user): Response
    {
        $user->setIsSignaled(false);
        $this->em->flush();

        return new Response("Utilisateur {$user->getEmail()} réactivé avec succès.");
    }
}
