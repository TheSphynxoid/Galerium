<?php

namespace App\Repository;

use App\Entity\Vote;
use App\Entity\Utilisateur;
use App\Entity\Concours;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Vote>
 */
class VoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vote::class);
    }

    /**
     * Vérifie si un visiteur a déjà voté pour un concours
     */
    public function hasVoted(Utilisateur $visiteur, Concours $concours): bool
    {
        $vote = $this->createQueryBuilder('v')
            ->where('v.visiteur = :visiteur')
            ->andWhere('v.concours = :concours')
            ->setParameter('visiteur', $visiteur)
            ->setParameter('concours', $concours)
            ->getQuery()
            ->getOneOrNullResult();

        return $vote !== null;
    }

    /**
     * Compte le nombre de votes pour une participation
     */
    public function countVotesForParticipation($participation): int
    {
        $result = $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.participation = :participation')
            ->setParameter('participation', $participation)
            ->getQuery()
            ->getSingleScalarResult();
        
        return (int) $result;
    }
}

