<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    private EntityManagerInterface $em;
    private UtilisateurRepository $userRepository;

    public function __construct(EntityManagerInterface $em, UtilisateurRepository $userRepository)
    {
        $this->em = $em;
        $this->userRepository = $userRepository;
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

    #[Route('/auth/registre', name: 'app_registre')]
    public function registre(): Response
    {
        return $this->render('auth/registre.html.twig');
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

        if ($request->isMethod('POST')) {
            $email = strtolower(trim($request->request->get('email', '')));
            $password = trim($request->request->get('password', ''));

            if (empty($email) || empty($password)) {
                $error = 'Please enter email and password.';
            } else {
                // Use the repository to find the user
                $user = $this->userRepository->findOneBy(['email' => $email]);

                if (!$user) {
                    $error = 'Invalid email or password.';
                } elseif ($user->getPassword() !== $password) { // Since password is plain text in your DB
                    $error = 'Invalid email or password.';
                } else {
                    $session->set('user_id', $user->getId());
                    $session->set('user_email', $user->getEmail());
                    $session->set('user_role', $user->getRole());
                    $session->set('user_name', $user->getFullName()); // Use full name

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
        $session = $request->getSession();
        $session->clear();
        return $this->redirectToRoute('app_login');
    }

    private function redirectToRoleRoute(string $role): Response
    {
        return match (strtoupper($role)) {
            'JURY' => $this->redirectToRoute('app_jury'),
            'ARTISTE' => $this->redirectToRoute('app_artiste'),
            'VISITEUR' => $this->redirectToRoute('app_visiteur'),
            'ADMIN' => $this->redirectToRoute('app_admin'),
            default => $this->redirectToRoute('app_login'),
        };
    }

    // Temporary route to create a test user matching your DB structure
    #[Route('/create-test-user', name: 'app_create_test_user')]
    public function createTestUser(): Response
    {
        $user = new Utilisateur();
        $user->setNom('Doe');
        $user->setPrenom('John');
        $user->setEmail('admin@example.com');
        $user->setPassword('admin123'); // Plain text password as in your DB
        $user->setRole('ADMIN');
        $user->setDateInscription(new \DateTime());

        $this->em->persist($user);
        $this->em->flush();

        return new Response('Test user created: admin@example.com / admin123 (Role: ADMIN)');
    }
}