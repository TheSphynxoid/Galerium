<?php

namespace App\Repository;

use App\Entity\Participation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Participation>
 */
class ParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participation::class);
    }

    //    /**
    //     * @return Participation[] Returns an array of Participation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Participation
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Récupère toutes les participations d'un artiste
     * @return Participation[]
     */
    public function findByArtiste($artiste): array
    {
        // Récupérer toutes les participations avec leur œuvre et concours
        $qb = $this->createQueryBuilder('p')
            ->select('p', 'o', 'c')
            ->innerJoin('p.oeuvre', 'o')
            ->leftJoin('p.concours', 'c')
            ->where('o.artiste = :artiste')
            ->setParameter('artiste', $artiste)
            ->orderBy('p.dateparticipation', 'DESC');
        
        return $qb->getQuery()->getResult();
    }
}
