<?php

namespace App\Controller;

use App\Entity\Enchere;
use App\Enum\EnchereStatut;
use App\Repository\OffreRepository;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Psr\Log\LoggerInterface;

class EncherePurchaseController extends AbstractController
{
    #[Route('/enchere/{id}/purchase', name: 'app_enchere_purchase', methods: ['GET'])]
    public function purchase(
        Enchere $enchere,
        Request $request,
        OffreRepository $offreRepository,
        UriSigner $uriSigner,
        #[Autowire('%env(STRIPE_SECRET_KEY)%')] string $stripeSecretKey
    ): Response {
        // Validate signed URL
        if (!$uriSigner->check($request->getUri())) {
            return new Response('Lien invalide ou expiré.', Response::HTTP_FORBIDDEN);
        }

        // Ensure auction is finished
        if ($enchere->getStatut() !== EnchereStatut::TERMINEE->value) {
            return new Response('Cette enchère n\'est pas encore terminée.', Response::HTTP_FORBIDDEN);
        }

        $winningOffer = $offreRepository->findHighestByEchere($enchere);
        if (!$winningOffer) {
            return new Response('Aucune offre trouvée pour cette enchère.', Response::HTTP_NOT_FOUND);
        }

        // Initiate Stripe checkout for winning amount
        $amountCents = (int) round($winningOffer->getMontant() * 100);
        if ($amountCents <= 0) {
            return new Response('Montant invalide.', Response::HTTP_BAD_REQUEST);
        }

        Stripe::setApiKey($stripeSecretKey);

        $oeuvre = $enchere->getOeuvre();

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $oeuvre?->getTitle() ?? ('Enchère #' . $enchere->getId()),
                        'description' => $oeuvre && $oeuvre->getArtiste() ? ('Œuvre par ' . $oeuvre->getArtiste()->getDisplayName()) : null,
                    ],
                    'unit_amount' => $amountCents,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('app_enchere_purchase_success', ['id' => $enchere->getId()], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->generateUrl('app_enchere_purchase_cancel', ['id' => $enchere->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return $this->redirect($session->url, 303);
    }

    #[Route('/enchere/{id}/purchase/success', name: 'app_enchere_purchase_success', methods: ['GET'])]
    public function success(
        Enchere $enchere,
        OffreRepository $offreRepository,
        MailerInterface $mailer,
        LoggerInterface $logger,
        Request $request
    ): Response
    {
        $winningOffer = $offreRepository->findHighestByEchere($enchere);
        if (!$winningOffer) {
            $this->addFlash('warning', 'Aucune offre trouvée pour cette enchère.');
            return $this->redirectToRoute('app_enchere_show', ['id' => $enchere->getId()]);
        }

        $buyer = $winningOffer->getUser();
        $buyerEmail = $buyer?->getEmail();

        if ($buyerEmail) {
            $email = (new TemplatedEmail())
                ->from($_ENV['MAILER_FROM_EMAIL'] ?? 'noreply@galerium.com')
                ->to($buyerEmail)
                ->subject('Confirmation d\'achat - ' . ($enchere->getOeuvre()?->getTitle() ?? 'Enchère #' . $enchere->getId()))
                ->htmlTemplate('emails/purchase_confirmation.html.twig')
                ->context([
                    'oeuvre' => $enchere->getOeuvre(),
                ]);

            try {
                $mailer->send($email);
            } catch (\Throwable $e) {
                $logger->error('Erreur envoi email confirmation enchere', [
                    'enchere_id' => $enchere->getId(),
                    'error' => $e->getMessage(),
                ]);
                $this->addFlash('warning', 'Paiement confirmé, mais l\'email n\'a pas pu être envoyé.');
            }
        }

        $this->addFlash('success', 'Paiement de l\'enchère confirmé. Un email de confirmation vous a été envoyé.');

        return $this->render('enchere/purchase_success.html.twig', [
            'enchere' => $enchere,
            'offer' => $winningOffer,
            'emailSentTo' => $buyerEmail,
            'sessionId' => $request->query->get('session_id'),
        ]);
    }

    #[Route('/enchere/{id}/purchase/cancel', name: 'app_enchere_purchase_cancel', methods: ['GET'])]
    public function cancel(Enchere $enchere): Response
    {
        $this->addFlash('warning', 'Le paiement de l\'enchère a été annulé.');
        return $this->redirectToRoute('app_enchere_show', ['id' => $enchere->getId()]);
    }
}
