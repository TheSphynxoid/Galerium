<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\TooManyPasswordRequestsException;
use Symfony\Component\Mailer\MailerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    #[Route('/forgot-password', name: 'app_forgot_password_request')]
    public function request(
        Request $request,
        ResetPasswordHelperInterface $resetPasswordHelper,
        MailerInterface $mailer,
        EntityManagerInterface $em,
        LoggerInterface $logger
    ): Response {
        $form = $this->createFormBuilder()
            ->add('email', EmailType::class, ['label' => 'Votre email'])
            ->add('submit', SubmitType::class, ['label' => 'Envoyer le lien de réinitialisation'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $em->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);

            if ($user) {
                try {
                    // use ResetPasswordHelper to throttle requests
                    $resetPasswordHelper->generateResetToken($user);

                    // generate a 5-digit numeric code
                    $code = str_pad((string)random_int(0, 99999), 5, '0', STR_PAD_LEFT);

                    // store hashed code for security and expiry (15 minutes)
                    $user->setResetCode(password_hash($code, PASSWORD_DEFAULT));
                    $user->setResetCodeExpiresAt((new \DateTimeImmutable())->add(new \DateInterval('PT15M')));
                    $em->persist($user);
                    $em->flush();

                    // Log code for debugging (never log in production with real credentials)
                    $logger->info('Reset code generated for {email}: {code}', ['email' => $user->getEmail(), 'code' => $code]);

                    $mailerDsn = $_ENV['MAILER_DSN'] ?? getenv('MAILER_DSN');
                    $mailerNotConfigured = empty($mailerDsn) || str_starts_with((string)$mailerDsn, 'null://');

                    if ($mailerNotConfigured) {
                        // Mailer not configured: inform the user in the UI (only in dev/test)
                        $this->addFlash('warning', 'Le service d\'envoi de mail n\'est pas configuré. Le code est affiché pour test.');
                        $this->addFlash('info', sprintf('Code de réinitialisation (test) : %s', $code));
                    } else {
                        $emailHtml = $this->renderView('reset_password/code_email.html.twig', [
                            'resetCode' => $code,
                            'expiresAt' => $user->getResetCodeExpiresAt(),
                        ]);

                        $emailMessage = (new Email())
                            ->from('no-reply@mon-site.com')
                            ->to($user->getEmail())
                            ->subject('Code de réinitialisation - Galerium')
                            ->html($emailHtml);

                        $mailer->send($emailMessage);
                        if ($this->getParameter('kernel.environment') === 'dev' || $request->query->get('show_link')) {
                            $this->addFlash('info', sprintf('Code de réinitialisation (test) : %s', $code));
                        }
                    }
                } catch (TooManyPasswordRequestsException $e) {
                    $retry = $e->getRetryAfter();
                    $this->addFlash('warning', $retry > 0
                        ? sprintf('Vous devez attendre %d seconde(s) avant de réessayer.', $retry)
                        : 'Vous avez récemment demandé une réinitialisation. Veuillez vérifier votre email.');

                    return $this->redirectToRoute('app_forgot_password_request');
                }
            }

            $this->addFlash('success', 'Si l’email existe, un code de réinitialisation a été envoyé. Vérifiez votre e-mail.');
            return $this->redirectToRoute('app_reset_password_enter_code');
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function reset(
        Request $request,
        string $token,
        ResetPasswordHelperInterface $resetPasswordHelper,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
        try {
            $user = $resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Le lien de réinitialisation est invalide ou expiré.');
            return $this->redirectToRoute('app_forgot_password_request');
        }

        $form = $this->createFormBuilder()
            ->add('plainPassword', PasswordType::class, ['label' => 'Nouveau mot de passe'])
            ->add('confirmPassword', PasswordType::class, ['label' => 'Confirmer le mot de passe'])
            ->add('submit', SubmitType::class, ['label' => 'Réinitialiser le mot de passe'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('plainPassword')->getData();
            $confirmPassword = $form->get('confirmPassword')->getData();

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->render('reset_password/reset.html.twig', [
                    'resetForm' => $form->createView(),
                ]);
            }
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));

            $em->persist($user);
            $em->flush();

            $resetPasswordHelper->removeResetRequest($token);

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

    #[Route('/reset-password/enter-code', name: 'app_reset_password_enter_code')]
    public function enterCode(
        Request $request,
        ResetPasswordHelperInterface $resetPasswordHelper,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createFormBuilder()
            ->add('email', EmailType::class, ['label' => 'Votre email'])
            ->add('code', TextType::class, [
                'label' => 'Code (5 chiffres)',
                'attr' => ['maxlength' => 5, 'pattern' => '\\d{5}', 'inputmode' => 'numeric', 'placeholder' => 'e.g. 12345'],
                'constraints' => [new \Symfony\Component\Validator\Constraints\Regex(['pattern' => '/^\d{5}$/', 'message' => 'Le code doit contenir exactement 5 chiffres.'])]
            ])
            ->add('submit', SubmitType::class, ['label' => "Valider le code"])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $code = $form->get('code')->getData();

            $user = $em->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
            if (!$user) {
                $this->addFlash('error', 'Utilisateur introuvable.');
                return $this->redirectToRoute('app_reset_password_enter_code');
            }

            $expiresAt = $user->getResetCodeExpiresAt();
            $hashed = $user->getResetCode();

            if (!$hashed || !$expiresAt || $expiresAt < new \DateTimeImmutable() || !password_verify($code, $hashed)) {
                $this->addFlash('error', 'Code invalide ou expiré.');
                return $this->redirectToRoute('app_reset_password_enter_code');
            }

            // Clear stored code
            $user->setResetCode(null);
            $user->setResetCodeExpiresAt(null);
            $em->persist($user);
            $em->flush();

            // Generate a reset token and redirect to reset page
            $resetToken = $resetPasswordHelper->generateResetToken($user);
            return $this->redirectToRoute('app_reset_password', ['token' => $resetToken->getToken()]);
        }

        return $this->render('reset_password/enter_code.html.twig', [
            'enterCodeForm' => $form->createView(),
        ]);
    }
}
