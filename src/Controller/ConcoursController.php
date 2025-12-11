<?php

namespace App\Controller;

use App\Entity\Concours;
use App\Entity\Vote;
use App\Form\ConcoursType;
use App\Repository\ConcoursRepository;
use App\Repository\ParticipationRepository;
use App\Repository\VoteRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/concours')]
final class ConcoursController extends AbstractController
{
    #[Route(name: 'app_concours_index', methods: ['GET'])]
    public function index(ConcoursRepository $concoursRepository): Response
    {
        // On récupère simplement tous les concours sans filtre
        $concours = $concoursRepository->findAll();

        return $this->render('concours/index.html.twig', [
            'concours' => $concours,
        ]);
    }


    #[Route('/new', name: 'app_concours_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $concour = new Concours();
        $form = $this->createForm(ConcoursType::class, $concour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($concour);
            $entityManager->flush();

            return $this->redirectToRoute('app_concours_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('concours/new.html.twig', [
            'concour' => $concour,
            'form' => $form,
        ]);
    }

    #[Route('/{id<\d+>}', name: 'app_concours_show', methods: ['GET'])]
    public function show(Concours $concour): Response
    {
        return $this->render('concours/show.html.twig', [
            'concour' => $concour,
        ]);
    }

    #[Route('/{id<\d+>}/edit', name: 'app_concours_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Concours $concour, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ConcoursType::class, $concour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_concours_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('concours/edit.html.twig', [
            'concour' => $concour,
            'form' => $form,
        ]);
    }

    #[Route('/{id<\d+>}', name: 'app_concours_delete', methods: ['POST'])]
    public function delete(Request $request, Concours $concour, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$concour->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($concour);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_concours_index', [], Response::HTTP_SEE_OTHER);
    }

#[Route('/visiteur', name: 'app_concours_visiteur_index', methods: ['GET'])]
public function visiteur(Request $request, ConcoursRepository $concoursRepository): Response
{
    $title = $request->query->get('title');

    if ($title) {
        $concours = $concoursRepository->createQueryBuilder('c')
            ->where('c.titre LIKE :t')
            ->setParameter('t', '%' . $title . '%')
            ->getQuery()
            ->getResult();
    } else {
        $concours = $concoursRepository->findAll();
    }

    // Si c'est une requête AJAX, retourner uniquement les résultats
    if ($request->isXmlHttpRequest() || $request->query->get('ajax')) {
        return $this->render('concours/visiteur.html.twig', [
            'concours' => $concours,
            'title' => $title,
        ]);
    }

    return $this->render('concours/visiteur.html.twig', [
        'concours' => $concours,
        'title' => $title,
    ]);
}

#[Route('/visiteur/{id<\d+>}/oeuvres', name: 'app_concours_visiteur_oeuvres', methods: ['GET'])]
public function visiteurOeuvres(
    Concours $concours,
    Request $request,
    ParticipationRepository $participationRepository,
    VoteRepository $voteRepository,
    UtilisateurRepository $userRepository
): Response {
    // Récupérer toutes les participations de ce concours
    $participations = $participationRepository->createQueryBuilder('p')
        ->innerJoin('p.concours', 'c')
        ->where('c.id = :concoursId')
        ->setParameter('concoursId', $concours->getId())
        ->getQuery()
        ->getResult();

    // Vérifier si l'utilisateur a déjà voté
    $hasVoted = false;
    $votedParticipation = null;
    $isConnected = false;
    $session = $request->getSession();
    if ($session->has('user_id')) {
        $user = $userRepository->find($session->get('user_id'));
        if ($user) {
            $isConnected = true;
            $hasVoted = $voteRepository->hasVoted($user, $concours);
            if ($hasVoted) {
                $vote = $voteRepository->createQueryBuilder('v')
                    ->where('v.visiteur = :visiteur')
                    ->andWhere('v.concours = :concours')
                    ->setParameter('visiteur', $user)
                    ->setParameter('concours', $concours)
                    ->getQuery()
                    ->getOneOrNullResult();
                if ($vote) {
                    $votedParticipation = $vote->getParticipation();
                }
            }
        }
    }

    // Compter les votes pour chaque participation depuis la base de données
    $votesCount = [];
    foreach ($participations as $participation) {
        $votesCount[$participation->getId()] = $voteRepository->countVotesForParticipation($participation);
    }

    return $this->render('concours/visiteur_oeuvres.html.twig', [
        'concours' => $concours,
        'participations' => $participations,
        'hasVoted' => $hasVoted,
        'votedParticipation' => $votedParticipation,
        'votesCount' => $votesCount,
        'isConnected' => $isConnected,
    ]);
}

#[Route('/visiteur/{id<\d+>}/voter/{participationId<\d+>}', name: 'app_concours_visiteur_voter', methods: ['POST'])]
public function voter(
    Concours $concours,
    int $participationId,
    Request $request,
    ParticipationRepository $participationRepository,
    VoteRepository $voteRepository,
    UtilisateurRepository $userRepository,
    EntityManagerInterface $entityManager
): Response {
    // Vérifier la session
    $session = $request->getSession();
    if (!$session->has('user_id')) {
        $this->addFlash('error', 'Vous devez être connecté pour voter.');
        return $this->redirectToRoute('app_concours_visiteur_oeuvres', ['id' => $concours->getId()]);
    }

    $user = $userRepository->find($session->get('user_id'));
    if (!$user) {
        $this->addFlash('error', 'Utilisateur introuvable.');
        return $this->redirectToRoute('app_concours_visiteur_oeuvres', ['id' => $concours->getId()]);
    }

    // Vérifier que l'utilisateur est un visiteur
    if (!in_array('ROLE_VISITEUR', $user->getRoles())) {
        $this->addFlash('error', 'Seuls les visiteurs peuvent voter.');
        return $this->redirectToRoute('app_concours_visiteur_oeuvres', ['id' => $concours->getId()]);
    }

    // Vérifier si le vote public est activé pour ce concours
    if (!$concours->isVotePublic()) {
        $this->addFlash('error', 'Vote concours fermé.');
        return $this->redirectToRoute('app_concours_visiteur_oeuvres', ['id' => $concours->getId()]);
    }

    // Récupérer la participation
    $participation = $participationRepository->find($participationId);
    if (!$participation) {
        $this->addFlash('error', 'Participation introuvable.');
        return $this->redirectToRoute('app_concours_visiteur_oeuvres', ['id' => $concours->getId()]);
    }

    // Vérifier que la participation appartient au concours
    $participationConcours = $participation->getConcours();
    if (!$participationConcours->contains($concours)) {
        $this->addFlash('error', 'Cette participation n\'appartient pas à ce concours.');
        return $this->redirectToRoute('app_concours_visiteur_oeuvres', ['id' => $concours->getId()]);
    }

    // Vérifier le token CSRF
    $token = $request->request->get('_token');
    if (!$this->isCsrfTokenValid('vote_' . $participationId, $token)) {
        $this->addFlash('error', 'Token de sécurité invalide.');
        return $this->redirectToRoute('app_concours_visiteur_oeuvres', ['id' => $concours->getId()]);
    }

    // Vérifier si l'utilisateur a déjà voté pour ce concours
    $existingVote = $voteRepository->findVoteByVisiteurAndConcours($user, $concours);
    $oldParticipation = null;
    
    if ($existingVote) {
        // Modifier le vote existant
        $oldParticipation = $existingVote->getParticipation();
        $existingVote->setParticipation($participation);
        $existingVote->setDateVote(new \DateTime());
        
        $entityManager->flush();
        $this->addFlash('success', 'Votre vote a été modifié avec succès !');
    } else {
        // Créer un nouveau vote
        $vote = new Vote();
        $vote->setVisiteur($user);
        $vote->setParticipation($participation);
        $vote->setConcours($concours);
        $vote->setDateVote(new \DateTime());

        $entityManager->persist($vote);
        $entityManager->flush();
        $this->addFlash('success', 'Votre vote a été enregistré avec succès !');
    }

    // Synchroniser le compteur de votes de l'œuvre avec le nombre réel de votes
    $oeuvre = $participation->getOeuvre();
    if ($oeuvre) {
        // Compter tous les votes pour cette œuvre (toutes participations confondues)
        $totalVotes = 0;
        foreach ($oeuvre->getParticipations() as $part) {
            $totalVotes += $voteRepository->countVotesForParticipation($part);
        }
        $oeuvre->setVotesCount($totalVotes);
        $entityManager->flush();
    }

    // Si le vote a été modifié, mettre à jour aussi l'ancienne œuvre
    if ($oldParticipation && $oldParticipation->getOeuvre()) {
        $oldOeuvre = $oldParticipation->getOeuvre();
        $totalVotes = 0;
        foreach ($oldOeuvre->getParticipations() as $part) {
            $totalVotes += $voteRepository->countVotesForParticipation($part);
        }
        $oldOeuvre->setVotesCount($totalVotes);
        $entityManager->flush();
    }

    return $this->redirectToRoute('app_concours_visiteur_oeuvres', ['id' => $concours->getId()]);
}






#[Route('/artistev', name: 'app_concours_artistev_index', methods: ['GET'])]
public function artistev(Request $request, ConcoursRepository $concoursRepository): Response
{
    $title = $request->query->get('title');
    $statut = $request->query->get('statut');

    $qb = $concoursRepository->createQueryBuilder('c');

    if ($title) {
        $qb->andWhere('c.titre LIKE :t')
           ->setParameter('t', '%' . $title . '%');
    }

    if ($statut) {
        $qb->andWhere('c.statut = :s')
           ->setParameter('s', $statut);
    }

    $concours = $qb->getQuery()->getResult();

    // Si c'est une requête AJAX, retourner uniquement les résultats
    if ($request->isXmlHttpRequest() || $request->query->get('ajax')) {
        return $this->render('concours/artistev.html.twig', [
            'concours' => $concours,
            'title' => $title,
            'statut' => $statut,
        ]);
    }

    return $this->render('concours/artistev.html.twig', [
        'concours' => $concours,
        'title' => $title,
        'statut' => $statut,
    ]);
}




    // ------------------------------------
    // 🔵 GÉNÉRATION DU PDF
    // ------------------------------------
   #[Route('/pdf', name: 'app_concours_pdf', methods: ['GET'])]
public function pdf(ConcoursRepository $concoursRepository): Response
{
    $concours = $concoursRepository->findAll();

    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $dompdf = new Dompdf($options);

    // Génération du HTML
    $html = $this->renderView('concours/pdf.html.twig', [
        'concours' => $concours,
    ]);

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // ❗ LA PARTIE IMPORTANTE : récupérer le PDF dans une variable
    $output = $dompdf->output();

    return new Response(
        $output,
        200,
        [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="liste_concours.pdf"'
        ]
    );
}





#[Route('/qrcode/liste', name: 'app_concours_qrcode_liste')]
public function qrcodeListe(): Response
{
    // URL ABSOLUE
    $url = $this->generateUrl('app_concours_visiteur_index', [], UrlGeneratorInterface::ABSOLUTE_URL);

    // Génération QRCode (version Endroid 6.x)
    $qrCode = new QrCode(
        data: $url,
        encoding: new Encoding('UTF-8'),
        errorCorrectionLevel: ErrorCorrectionLevel::High,
        size: 300,
        margin: 20
    );
    
    $writer = new PngWriter();
    $result = $writer->write($qrCode);

    return new Response(
        $result->getString(),
        200,
        [
            'Content-Type' => $result->getMimeType(),
            'Cache-Control' => 'no-cache, no-store, must-revalidate'
        ]
    );
}




















}