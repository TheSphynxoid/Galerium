<?php

namespace App\Controller;

use App\Entity\Participation;
use App\Form\ParticipationType;
use App\Form\ParticipationEditType;
use App\Form\ParticipationStatusEditType;
use App\Repository\ParticipationRepository;
use App\Repository\ConcoursRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\OeuvreRepository;
use App\Repository\VoteRepository;
use App\Service\MqttService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

#[Route('/participation')]
final class ParticipationController extends AbstractController
{
    #[Route(name: 'app_participation_index', methods: ['GET'])]
    public function index(
        ParticipationRepository $participationRepository,
        ConcoursRepository $concoursRepository,
        VoteRepository $voteRepository
    ): Response {
        $participations = $participationRepository->findAll();
        
        // Compter les votes pour chaque participation depuis la base de données
        $votesCount = [];
        foreach ($participations as $participation) {
            $votesCount[$participation->getId()] = $voteRepository->countVotesForParticipation($participation);
        }
        
        return $this->render('participation/index.html.twig', [
            'participations' => $participations,
            'concoursList' => $concoursRepository->findAll(),
            'votesCount' => $votesCount,
        ]);
    }

    #[Route('/new/{concoursId}', name: 'app_participation_new', methods: ['GET', 'POST'])]
    public function new(
        int $concoursId,
        Request $request,
        EntityManagerInterface $entityManager,
        ConcoursRepository $concoursRepository,
        UtilisateurRepository $userRepository,
        OeuvreRepository $oeuvreRepository,
        ParticipationRepository $participationRepository,
        MqttService $mqttService
    ): Response {
        // Vérifier que l'utilisateur est connecté
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($session->get('user_id'));
        if (!$user || !in_array('ROLE_ARTISTE', $user->getRoles())) {
            $this->addFlash("error", "Vous devez être un artiste pour participer à un concours.");
            return $this->redirectToRoute('app_participation_index');
        }

        $artiste = $user->getArtiste();
        if (!$artiste) {
            $this->addFlash("error", "Profil artiste introuvable.");
            return $this->redirectToRoute('app_participation_index');
        }

        $concours = $concoursRepository->find($concoursId);
        if (!$concours) {
            throw $this->createNotFoundException("Concours introuvable !");
        }

        // Vérifier si l'artiste a déjà participé à ce concours
        if ($participationRepository->hasArtisteParticipatedInConcours($artiste, $concours)) {
            $this->addFlash("error", "Vous avez déjà participé au concours '{$concours->getTitre()}'. ");
            return $this->redirectToRoute('app_concours_artistev_index');
        }

        $participation = new Participation();

        //  Initialise les champs pour éviter NOT NULL
        $participation->setDateparticipation(new \DateTime());
        $participation->setStatut('en_cours');
        $participation->setVotepublic(false); 
        $participation->addConcour($concours);

        $form = $this->createForm(ParticipationType::class, $participation, [
            'artiste' => $artiste,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($participation);
            $entityManager->flush();

            // Send MQTT message
            $userPhone = $user->getTelephone();
            if ($userPhone) {
                $message = sprintf(
                    "Votre participation au concours '%s' a été enregistrée avec succès. Merci pour votre participation !",
                    $concours->getTitre()
                );
                $mqttService->sendSms($userPhone, $message);
            }

            $this->addFlash("success", "Participation envoyée avec succès !");
            return $this->redirectToRoute('app_concours_artistev_index');
        }

        // Récupérer les œuvres de l'artiste pour l'affichage visuel
        $oeuvres = $oeuvreRepository->findByArtiste($artiste);

        return $this->render('participation/new.html.twig', [
            'concours' => $concours,
            'participation' => $participation,
            'form' => $form->createView(),
            'oeuvres' => $oeuvres,
        ]);
    }

    #[Route('/{id<\d+>}', name: 'app_participation_show', methods: ['GET'])]
    public function show(
        Participation $participation,
        VoteRepository $voteRepository
    ): Response {
        // Compter les votes pour cette participation depuis la base de données
        $votesCount = $voteRepository->countVotesForParticipation($participation);
        
        return $this->render('participation/show.html.twig', [
            'participation' => $participation,
            'votesCount' => $votesCount,
        ]);
    }

    #[Route('/{id<\d+>}/edit', name: 'app_participation_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Participation $participation, 
        EntityManagerInterface $entityManager,
        UtilisateurRepository $userRepository,
        OeuvreRepository $oeuvreRepository
    ): Response {
        // Vérifier la session
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($session->get('user_id'));
        if (!$user || !in_array('ROLE_ARTISTE', $user->getRoles())) {
            $this->addFlash("error", "Vous devez être artiste pour modifier une participation.");
            return $this->redirectToRoute('app_participation_my');
        }

        $artiste = $user->getArtiste();
        if (!$artiste) {
            $this->addFlash("error", "Profil artiste introuvable.");
            return $this->redirectToRoute('app_participation_my');
        }

        // Vérifier que la participation appartient à l'artiste wala le
        if (!$participation->getOeuvre() || $participation->getOeuvre()->getArtiste() !== $artiste) {
            $this->addFlash("error", "Vous n'avez pas le droit de modifier cette participation.");
            return $this->redirectToRoute('app_participation_my');
        }

        $form = $this->createForm(ParticipationEditType::class, $participation, [
            'artiste' => $artiste,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash("success", "Participation modifiée avec succès !");
            return $this->redirectToRoute('app_participation_my');
        }

        // Récupérer les œuvres de l'artiste pour l'affichage visuel
        $oeuvres = $oeuvreRepository->findByArtiste($artiste);

        return $this->render('participation/edit.html.twig', [
            'participation' => $participation,
            'form' => $form->createView(),
            'oeuvres' => $oeuvres,
        ]);
    }

    #[Route('/{id<\d+>}/edit-status', name: 'app_participation_edit_status', methods: ['GET', 'POST'])]
    public function editStatus(
        Request $request, 
        Participation $participation, 
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(ParticipationStatusEditType::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash("success", "Statut mis à jour avec succès !");
            return $this->redirectToRoute('app_participation_index');
        }

        return $this->render('participation/edit_status.html.twig', [
            'participation' => $participation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id<\d+>}', name: 'app_participation_delete', methods: ['POST'])]
    public function delete(Request $request, Participation $participation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$participation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($participation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_participation_my');
    }



#[Route('/mes-participations', name: 'app_participation_my', methods: ['GET'])]
public function myParticipations(
    Request $request,
    ParticipationRepository $participationRepository,
    UtilisateurRepository $userRepository,
    VoteRepository $voteRepository
): Response {
    // Vérifier la session
    $session = $request->getSession();
    if (!$session->has('user_id')) {
        return $this->redirectToRoute('app_login');
    }

    // Récupérer utilisateur connecté
    $user = $userRepository->find($session->get('user_id'));

    if (!$user || !in_array('ROLE_ARTISTE', $user->getRoles())) {
        $this->addFlash("error", "Vous devez être artiste pour accéder à vos participations.");
        return $this->redirectToRoute('app_participation_index');
    }

    $artiste = $user->getArtiste();
    if (!$artiste) {
        $this->addFlash("error", "Profil artiste introuvable.");
        return $this->redirectToRoute('app_participation_index');
    }

    // 🔥 Récupérer les participations via l'œuvre de l'artiste
    $participations = $participationRepository->findByArtiste($artiste);

    // Compter les votes pour chaque participation depuis la base de données
    $votesCount = [];
    foreach ($participations as $participation) {
        $votesCount[$participation->getId()] = $voteRepository->countVotesForParticipation($participation);
    }

    return $this->render('participation/my.html.twig', [
        'participations' => $participations,
        'votesCount' => $votesCount,
    ]);
}



#[Route('/qrcode/{id<\d+>}', name: 'app_participation_qrcode_single', methods: ['GET'])]
public function qrcodeSingle(
    Participation $participation,
    Request $request,
    UtilisateurRepository $userRepository,
    VoteRepository $voteRepository
): Response {
    // Vérifier la session
    $session = $request->getSession();
    if (!$session->has('user_id')) {
        return $this->render('participation/qrcode_single.html.twig', [
            'participation' => null,
            'votesCount' => 0,
            'error' => 'Vous devez être connecté pour voir cette participation.',
        ]);
    }

    // Récupérer utilisateur connecté
    $user = $userRepository->find($session->get('user_id'));

    if (!$user || !in_array('ROLE_ARTISTE', $user->getRoles())) {
        return $this->render('participation/qrcode_single.html.twig', [
            'participation' => null,
            'votesCount' => 0,
            'error' => 'Vous devez être artiste pour voir cette participation.',
        ]);
    }

    $artiste = $user->getArtiste();
    if (!$artiste) {
        return $this->render('participation/qrcode_single.html.twig', [
            'participation' => null,
            'votesCount' => 0,
            'error' => 'Profil artiste introuvable.',
        ]);
    }

    // Vérifier que la participation appartient à l'artiste
    if (!$participation->getOeuvre() || $participation->getOeuvre()->getArtiste() !== $artiste) {
        return $this->render('participation/qrcode_single.html.twig', [
            'participation' => null,
            'votesCount' => 0,
            'error' => 'Vous n\'avez pas accès à cette participation.',
        ]);
    }

    // Compter les votes pour cette participation
    $votesCount = $voteRepository->countVotesForParticipation($participation);

    return $this->render('participation/qrcode_single.html.twig', [
        'participation' => $participation,
        'votesCount' => $votesCount,
        'error' => null,
    ]);
}

#[Route('/qrcode/{id<\d+>}/generate', name: 'app_participation_qrcode_generate', methods: ['GET'])]
public function qrcodeGenerate(
    Participation $participation,
    Request $request,
    UtilisateurRepository $userRepository,
    VoteRepository $voteRepository
): Response {
    try {
        // Vérifier la session
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return new Response('Unauthorized', 401);
        }

        // Récupérer utilisateur connecté
        $user = $userRepository->find($session->get('user_id'));

        if (!$user || !in_array('ROLE_ARTISTE', $user->getRoles())) {
            return new Response('Unauthorized', 401); //Non autorisé

        }

        $artiste = $user->getArtiste();
        if (!$artiste) {
            return new Response('Unauthorized', 401);
        }

        // Vérifier que la participation appartient à l'artiste
        if (!$participation->getOeuvre() || $participation->getOeuvre()->getArtiste() !== $artiste) {
            return new Response('Unauthorized', 401);
        }

        // Compter les votes pour cette participation
        $votesCount = $voteRepository->countVotesForParticipation($participation);

        // Créer le contenu texte à encoder dans le QR
        $data = "CONFIRMATION DE PARTICIPATION\n\n";
        $data .= "=== PARTICIPATION ===\n";
        $data .= "ID: " . $participation->getId() . "\n";
        $data .= "Date participation: " . ($participation->getDateparticipation() ? $participation->getDateparticipation()->format('d/m/Y H:i') : '-') . "\n";
        $data .= "Statut: " . $participation->getStatut() . "\n";
        $data .= "Votes: " . $votesCount . " vote(s)\n";
        $data .= "Description: " . substr($participation->getDescription(), 0, 200) . "\n";

        // Informations sur le concours
        $concoursList = $participation->getConcours();
        if (!$concoursList->isEmpty()) {
            $data .= "\n=== CONCOURS ===\n";
            $concoursArray = $concoursList->toArray();
            foreach ($concoursArray as $index => $concours) {
                $data .= "Titre: " . $concours->getTitre() . "\n";
                if ($concours->getDescription()) {
                    $data .= "Description: " . $concours->getDescription() . "\n";
                }
                if ($concours->getDateDebut()) {
                    $data .= "Date début: " . $concours->getDateDebut()->format('d/m/Y H:i') . "\n";
                }
                if ($concours->getDateFin()) {
                    $data .= "Date fin: " . $concours->getDateFin()->format('d/m/Y H:i') . "\n";
                }
                if ($index < count($concoursArray) - 1) {
                    $data .= "---\n";
                }
            }
        }

        // Informations sur l'œuvre
        $oeuvre = $participation->getOeuvre();
        if ($oeuvre) {
            $data .= "\n=== ŒUVRE PRÉSENTÉE ===\n";
            $data .= "Titre: " . $oeuvre->getTitle() . "\n";
            if ($oeuvre->getDescription()) {
                $data .= "Description: " . $oeuvre->getDescription() . "\n";
            }
            if ($oeuvre->getArtiste()) {
                $artisteOeuvre = $oeuvre->getArtiste();
                if ($artisteOeuvre->getUser()) {
                    $utilisateurOeuvre = $artisteOeuvre->getUser();
                    $data .= "Artiste: " . $utilisateurOeuvre->getNom() . " " . $utilisateurOeuvre->getPrenom() . "\n";
                    if ($utilisateurOeuvre->getEmail()) {
                        $data .= "Email: " . $utilisateurOeuvre->getEmail() . "\n";
                    }
                    if ($utilisateurOeuvre->getTelephone()) {
                        $data .= "Téléphone: " . $utilisateurOeuvre->getTelephone() . "\n";
                    }
                }
            }
        }

        // Génération QRCode (version Endroid 6.x)
        $qrCode = new QrCode(
            data: $data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10
        );
        
        $writer = new SvgWriter();    // pour générer le QR code au format SVG.
        $result = $writer->write($qrCode);
        
        $svgContent = $result->getString();
        
        // Retourner le QR code comme réponse HTTP
        return new Response($svgContent, 200, [
            'Content-Type' => 'image/svg+xml; charset=utf-8',
            'Content-Disposition' => 'inline; filename="qrcode.svg"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    } catch (\Exception $e) {
        return new Response('Erreur lors de la génération du QR code: ' . $e->getMessage(), 500);
    }
}





























}
