<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Psr\Log\LoggerInterface;

class TestEmailController extends AbstractController
{
    #[Route('/send-test-email', name: 'app_send_test_email')]
    public function send(Request $request, MailerInterface $mailer, LoggerInterface $logger): Response
    {
        $form = $this->createFormBuilder()
            ->add('email', EmailType::class, ['label' => 'Adresse Gmail'])
            ->add('send', SubmitType::class, ['label' => 'Envoyer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $to = $form->get('email')->getData();

            try {
                $email = (new Email())
                    ->from($_SERVER['MAILER_FROM_EMAIL'] ?? 'bahajouila7@gmail.com')
                    ->to($to)
                    ->subject('✅ Test d\'envoi depuis Galerium')
                    ->html($this->getTestEmailContent());

                $mailer->send($email);
                
                $logger->info('Email envoyé avec succès à ' . $to);
                $this->addFlash('success', '✅ Email envoyé avec succès à ' . $to . '. Vérifiez votre boîte de réception et le dossier spam.');
                
            } catch (\Exception $e) {
                $errorMessage = 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage();
                $logger->error($errorMessage, ['exception' => $e]);
                $this->addFlash('error', $errorMessage);
            }

            return $this->redirectToRoute('app_send_test_email');
        }

        return $this->render('email/send_test.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function getTestEmailContent(): string
    {
        return <<<HTML
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                    .header { background-color: #4a6fa5; color: white; padding: 10px 20px; border-radius: 5px 5px 0 0; }
                    .content { padding: 20px; }
                    .footer { margin-top: 20px; font-size: 0.9em; color: #666; text-align: center; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>Test d\'envoi d\'email</h1>
                    </div>
                    <div class="content">
                        <p>Bonjour,</p>
                        <p>Ceci est un email de test envoyé depuis votre application Galerium.</p>
                        <p>Si vous recevez cet email, cela signifie que la configuration de l\'envoi d\'emails fonctionne correctement.</p>
                        <p>Date et heure d\'envoi : {now}</p>
                    </div>
                    <div class="footer">
                        <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
                    </div>
                </div>
            </body>
            </html>
            HTML;
    }
}
