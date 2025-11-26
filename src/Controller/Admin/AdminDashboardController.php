<?php

namespace App\Controller\Admin;

use App\Repository\ArtisteRepository;
use App\Repository\OeuvreRepository;
use App\Repository\ParticipationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminDashboardController extends AbstractController
{
    public function __construct(
        private ArtisteRepository $artisteRepository,
        private OeuvreRepository $oeuvreRepository,
        private ParticipationRepository $participationRepository
    ) {
    }

    #[Route('/dashboard', name: 'admin_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Statistiques générales
        $totalArtistes = $this->artisteRepository->count([]);
        $totalOeuvres = $this->oeuvreRepository->count([]);
        $totalParticipations = $this->participationRepository->count([]);

        // Artistes récents
        $recentArtistes = $this->artisteRepository->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Œuvres récentes
        $recentOeuvres = $this->oeuvreRepository->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Top artistes par nombre d'œuvres
        $topArtistesByOeuvres = $this->artisteRepository->createQueryBuilder('a')
            ->select('a, COUNT(o.id) as oeuvreCount')
            ->leftJoin('a.oeuvres', 'o')
            ->groupBy('a.id')
            ->orderBy('oeuvreCount', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Top artistes par votes
        $topArtistesByVotes = $this->artisteRepository->createQueryBuilder('a')
            ->select('a, SUM(o.nbVotes) as totalVotes')
            ->leftJoin('a.oeuvres', 'o')
            ->groupBy('a.id')
            ->orderBy('totalVotes', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Œuvres les plus votées
        $topOeuvres = $this->oeuvreRepository->createQueryBuilder('o')
            ->orderBy('o.nbVotes', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Statistiques par artiste
        $artistesStats = [];
        $allArtistes = $this->artisteRepository->findAll();
        foreach ($allArtistes as $artiste) {
            $oeuvres = $artiste->getOeuvres();
            $participations = $this->participationRepository->findByArtiste($artiste);
            
            $artistesStats[] = [
                'artiste' => $artiste,
                'nbOeuvres' => count($oeuvres),
                'nbParticipations' => count($participations),
                'nbVotes' => array_sum(array_map(fn($o) => $o->getNbVotes(), $oeuvres->toArray())),
                'nbCommentaires' => array_sum(array_map(fn($o) => $o->getNbCommentaires(), $oeuvres->toArray())),
            ];
        }

        // Trier par nombre de votes
        usort($artistesStats, fn($a, $b) => $b['nbVotes'] <=> $a['nbVotes']);

        return $this->render('admin/dashboard.html.twig', [
            'totalArtistes' => $totalArtistes,
            'totalOeuvres' => $totalOeuvres,
            'totalParticipations' => $totalParticipations,
            'recentArtistes' => $recentArtistes,
            'recentOeuvres' => $recentOeuvres,
            'topArtistesByOeuvres' => $topArtistesByOeuvres,
            'topArtistesByVotes' => $topArtistesByVotes,
            'topOeuvres' => $topOeuvres,
            'artistesStats' => $artistesStats,
        ]);
    }
}



