<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\VerifyEmailCodeType;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class EmailVerificationController extends AbstractController
{
    private $emailVerificationService;
    private $em;
    private $security;

    public function __construct(
        EmailVerificationService $emailVerificationService, 
        EntityManagerInterface $em,
        Security $security
    ) {
        $this->emailVerificationService = $emailVerificationService;
        $this->em = $em;
        $this->security = $security;
    }

    /**
     * @Route("/send-verification", name="app_send_verification")
     */
    public function sendVerification(): Response
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }

        if ($this->emailVerificationService->sendVerificationEmail($user)) {
            $this->addFlash('success', 'Un code de vérification a été envoyé à votre adresse email.');
        } else {
            $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi du code de vérification. Veuillez réessayer.');
        }

        return $this->redirectToRoute('app_verify_email');
    }

    /**
     * @Route("/verify-email", name="app_verify_email")
     */
    public function verifyEmail(Request $request): Response
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->isVerified()) {
            $this->addFlash('info', 'Votre adresse email est déjà vérifiée.');
            return $this->redirectToRoute('app_profile');
        }

        $form = $this->createForm(VerifyEmailCodeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $code = $form->get('code')->getData();
            
            if ($this->emailVerificationService->verifyUser($user, $code)) {
                $this->addFlash('success', 'Votre adresse email a été vérifiée avec succès !');
                return $this->redirectToRoute('app_profile');
            }

            $this->addFlash('error', 'Code de vérification invalide ou expiré.');
        }

        return $this->render('email_verification/verify.html.twig', [
            'verifyForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/check-verification", name="app_check_verification")
     */
    public function checkVerification(): Response
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            return $this->json(['isVerified' => false]);
        }

        return $this->json([
            'isVerified' => $user->isVerified(),
            'email' => $user->getEmail()
        ]);
    }
}
