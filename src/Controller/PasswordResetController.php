<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\ResetPasswordRequestFormType;
use App\Form\ResetPasswordFormType;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class PasswordResetController extends AbstractController
{
    private $resetPasswordHelper;
    private $entityManager;

    public function __construct(ResetPasswordHelperInterface $resetPasswordHelper, EntityManagerInterface $entityManager)
    {
        $this->resetPasswordHelper = $resetPasswordHelper;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/forgot-password", name="app_forgot_password_request")
     */
    public function request(Request $request, MailerInterface $mailer, UtilisateurRepository $userRepository): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user) {
                // Generate a 5-digit verification code
                $verificationCode = str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);
                
                // Store the verification code in the user's session
                $session = $request->getSession();
                $session->set('reset_password_code', $verificationCode);
                $session->set('reset_password_email', $email);
                $session->set('reset_password_code_time', time());

                // Send email with verification code
                $email = (new Email())
                    ->from('noreply@galerium.com')
                    ->to($user->getEmail())
                    ->subject('Your password reset code')
                    ->html($this->renderView('emails/reset_password.html.twig', [
                        'code' => $verificationCode,
                    ]));

                $mailer->send($email);

                return $this->redirectToRoute('app_verify_code');
            }

            $this->addFlash('success', 'If an account exists for this email, a verification code has been sent.');
        }

        return $this->render('security/forgot_password.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/verify-code", name="app_verify_code")
     */
    public function verifyCode(Request $request): Response
    {
        $session = $request->getSession();
        
        // Check if email exists in session
        if (!$session->has('reset_password_email')) {
            return $this->redirectToRoute('app_forgot_password_request');
        }

        // Check if code has expired (10 minutes)
        if (time() - $session->get('reset_password_code_time') > 600) {
            $this->addFlash('error', 'The verification code has expired. Please try again.');
            return $this->redirectToRoute('app_forgot_password_request');
        }

        if ($request->isMethod('POST')) {
            $submittedCode = $request->request->get('verification_code');
            $storedCode = $session->get('reset_password_code');

            if ($submittedCode === $storedCode) {
                $session->set('code_verified', true);
                return $this->redirectToRoute('app_reset_password');
            }

            $this->addFlash('error', 'Invalid verification code. Please try again.');
        }

        return $this->render('security/verify_code.html.twig');
    }

    /**
     * @Route("/reset-password", name="app_reset_password")
     */
    public function reset(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $session = $request->getSession();
        
        // Check if code is verified
        if (!$session->has('code_verified') || !$session->get('code_verified')) {
            return $this->redirectToRoute('app_forgot_password_request');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->entityManager->getRepository(Utilisateur::class)->findOneBy([
                'email' => $session->get('reset_password_email')
            ]);

            if ($user) {
                // Encode the plain password, and set it
                $encodedPassword = $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                );

                $user->setPassword($encodedPassword);
                $this->entityManager->flush();

                // Clear the session
                $session->remove('reset_password_email');
                $session->remove('reset_password_code');
                $session->remove('reset_password_code_time');
                $session->remove('code_verified');

                $this->addFlash('success', 'Your password has been reset successfully.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/reset_password.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }
}
