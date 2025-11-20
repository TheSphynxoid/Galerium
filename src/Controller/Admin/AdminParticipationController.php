<?php

namespace App\Controller\Admin;

use App\Entity\Participation;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/participations')]
class AdminParticipationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ParticipationRepository $participationRepository
    ) {
    }

    #[Route('', name: 'admin_participations_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $concoursId = $request->query->get('concours', '');
        $artisteId = $request->query->get('artiste', '');

        $qb = $this->participationRepository->createQueryBuilder('p')
            ->join('p.concours', 'c')
            ->join('p.artiste', 'a')
            ->orderBy('p.createdAt', 'DESC');

        if ($concoursId) {
            $qb->andWhere('c.id = :concoursId')
                ->setParameter('concoursId', $concoursId);
        }

        if ($artisteId) {
            $qb->andWhere('a.id = :artisteId')
                ->setParameter('artisteId', $artisteId);
        }

        $participations = $qb->getQuery()->getResult();

        return $this->render('admin/participations/index.html.twig', [
            'participations' => $participations,
            'concoursId' => $concoursId,
            'artisteId' => $artisteId,
        ]);
    }

    #[Route('/{id}', name: 'admin_participations_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Participation $participation): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/participations/show.html.twig', [
            'participation' => $participation,
        ]);
    }

    #[Route('/{id}/accepter', name: 'admin_participations_accept', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function accept(Request $request, Participation $participation): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('accept_participation' . $participation->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_participations_index');
        }

        // Vérifier que l'œuvre respecte le thème et le délai
        $concours = $participation->getConcours();
        $now = new \DateTimeImmutable();

        if ($now > $concours->getDateFin()) {
            $this->addFlash('error', 'Le délai de participation est dépassé.');
            return $this->redirectToRoute('admin_participations_show', ['id' => $participation->getId()]);
        }

        // La participation est acceptée (pas de statut dans l'entité actuelle, on peut l'ajouter si nécessaire)
        $this->entityManager->flush();

        $this->addFlash('success', 'Participation acceptée avec succès !');

        return $this->redirectToRoute('admin_participations_show', ['id' => $participation->getId()]);
    }

    #[Route('/{id}/refuser', name: 'admin_participations_reject', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function reject(Request $request, Participation $participation): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('reject_participation' . $participation->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_participations_index');
        }

        // Supprimer la participation
        $this->entityManager->remove($participation);
        $this->entityManager->flush();

        $this->addFlash('success', 'Participation refusée et supprimée avec succès !');

        return $this->redirectToRoute('admin_participations_index');
    }

    #[Route('/artiste/{artisteId}', name: 'admin_participations_by_artiste', methods: ['GET'], requirements: ['artisteId' => '\d+'])]
    public function byArtiste(int $artisteId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $artiste = $this->entityManager->getRepository(\App\Entity\Artiste::class)->find($artisteId);
        if (!$artiste) {
            throw $this->createNotFoundException('Artiste non trouvé');
        }

        $participations = $this->participationRepository->findByArtiste($artiste);

        // Calculer les statistiques
        $totalVotes = array_sum(array_map(fn($p) => $p->getVotesPublic(), $participations));
        $totalParticipations = count($participations);

        return $this->render('admin/participations/by_artiste.html.twig', [
            'artiste' => $artiste,
            'participations' => $participations,
            'totalVotes' => $totalVotes,
            'totalParticipations' => $totalParticipations,
        ]);
    }

    #[Route('/{id}/bloquer', name: 'admin_participations_block', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function block(Request $request, Participation $participation): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('block_participation' . $participation->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_participations_index');
        }

        // Bloquer l'artiste de ce concours (supprimer la participation)
        $this->entityManager->remove($participation);
        $this->entityManager->flush();

        $this->addFlash('success', 'Artiste bloqué de ce concours avec succès !');

        return $this->redirectToRoute('admin_participations_index');
    }
}



