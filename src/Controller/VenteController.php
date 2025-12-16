<?php

namespace App\Controller;

use App\Entity\Commission;
use App\Entity\Oeuvre;
use App\Form\SaleType;
use App\Service\CommissionCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class VenteController extends AbstractController
{
    #[Route('/vente/{id}', name: 'app_vente')]
    public function vendre(
        Oeuvre $oeuvre,
        Request $request,
        #[Autowire('%env(STRIPE_SECRET_KEY)%')] string $stripeSecretKey
    ): Response {
        
        // Ensure price is valid
        if (!$oeuvre->getPrice()) {
            $this->addFlash('error', 'Cette œuvre n\'a pas de prix défini.');
            return $this->redirectToRoute('app_oeuvre_index');
        }

        $form = $this->createForm(SaleType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            Stripe::setApiKey($stripeSecretKey);

            $checkoutSession = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'cad', // Or 'eur', 'usd' - Assuming CAD or change as needed. User used TND in template, Stripe might not support TND directly widely or requires config. I'll use EUR for demo default? Or USD.
                        'product_data' => [
                            'name' => $oeuvre->getTitle(),
                            'description' => 'Œuvre par ' . ($oeuvre->getArtiste() ? $oeuvre->getArtiste()->getDisplayName() : 'Artiste Inconnu'),
                        ],
                        'unit_amount' => (int) ($oeuvre->getPrice() * 100), // Amount in cents
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $this->generateUrl('app_vente_success', ['id' => $oeuvre->getId()], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $this->generateUrl('app_vente_cancel', ['id' => $oeuvre->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

            return $this->redirect($checkoutSession->url, 303);
        }

        return $this->render('vente/vendre.html.twig', [
            'oeuvre' => $oeuvre,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/vente/success/{id}', name: 'app_vente_success')]
    public function success(
        Oeuvre $oeuvre,
        CommissionCalculator $calculator,
        EntityManagerInterface $em,
        \Symfony\Component\Mailer\MailerInterface $mailer,
        Request $request,
        #[Autowire('%env(STRIPE_SECRET_KEY)%')] string $stripeSecretKey
    ): Response {
        // 1️⃣ Calcul commission
        $commissionMontant = $calculator->calculerCommission($oeuvre); //appel service

        // 2️⃣ Création entity Commission
        $commission = new Commission();
        $commission->setOeuvre($oeuvre)
            ->setArtiste($oeuvre->getArtiste())
            ->setMontant($commissionMontant)
            ->setPrixFinal($oeuvre->getPrice());

        // 3️⃣ Sauvegarde
        $em->persist($commission);
        $em->flush(); // Save first to ensure transaction is committed before sending email

        // 4️⃣ Envoi de l'email
        try {
            $customerEmail = 'wajdimejbri631@gmail.com'; // Fallback email
            
            $sessionId = $request->query->get('session_id');
            if ($sessionId) {
                Stripe::setApiKey($stripeSecretKey);
                try {
                    $session = Session::retrieve($sessionId);
                    if ($session->customer_details && $session->customer_details->email) {
                        $customerEmail = $session->customer_details->email;
                    }
                } catch (\Exception $e) {
                    
                    // Log error but continue
                }
            }

            $email = (new \Symfony\Bridge\Twig\Mime\TemplatedEmail())
                ->from('no-reply@galerium.com') // Sender should be consistent
                ->to($customerEmail) // Send to the actual buyer
                ->subject('Confirmation d\'achat - ' . $oeuvre->getTitle())
                ->htmlTemplate('emails/purchase_confirmation.html.twig')
                ->context([
                    'oeuvre' => $oeuvre,
                ]);

            $mailer->send($email);
            $this->addFlash('success', 'Paiement réussi ! Un email de confirmation a été envoyé à ' . $customerEmail);
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            $this->addFlash('warning', 'Paiement réussi, mais l\'envoi de l\'email a échoué : ' . $e->getMessage());
        }

        return $this->render('vente/success.html.twig', [
            'oeuvre' => $oeuvre,
            'commission' => $commission
        ]);
    }

    #[Route('/vente/cancel/{id}', name: 'app_vente_cancel')]
    public function cancel(Oeuvre $oeuvre): Response
    {
        $this->addFlash('warning', 'Le paiement a été annulé.');
        return $this->redirectToRoute('app_vente', ['id' => $oeuvre->getId()]);
    }
}
