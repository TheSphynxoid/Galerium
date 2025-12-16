<?php

namespace App\Repository;

use App\Entity\Oeuvre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Oeuvre>
 */
class OeuvreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Oeuvre::class);
    }

    /**
     * @return Oeuvre[] Returns an array of Oeuvre objects
     */
    public function findByArtiste($artiste): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.artiste = :artiste')
            ->setParameter('artiste', $artiste)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get statistics for an artist
     */
    public function getStatisticsForArtiste($artiste): array
    {
        $qb = $this->createQueryBuilder('o')
            ->select('COUNT(o.id) as totalOeuvres')
            ->addSelect('SUM(o.nbVotes) as totalVotes')
            ->addSelect('SUM(o.nbCommentaires) as totalCommentaires')
            ->addSelect('AVG(o.nbVotes) as moyenneVotes')
            ->andWhere('o.artiste = :artiste')
            ->setParameter('artiste', $artiste);

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * Find public artworks by search query (title or artist name)
     */
    public function findPublicBySearch(?string $query): array
    {
        $qb = $this->createQueryBuilder('o')
            ->join('o.artiste', 'a')
            ->andWhere('o.status = :status')
            ->setParameter('status', Oeuvre::STATUS_PUBLIC)
            ->orderBy('o.createdAt', 'DESC');

        if ($query) {
            $qb->andWhere('o.title LIKE :query OR a.displayName LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        return $qb->getQuery()->getResult();
    }
}







