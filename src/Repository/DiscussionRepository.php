<?php

namespace App\Repository;

use App\Entity\Discussion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Discussion>
 */
class DiscussionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Discussion::class);
    }


    // src/Repository/DiscussionRepository.php

public function searchByForumAndTitle(int $forumId, string $term): array
{
    return $this->createQueryBuilder('d')
        ->andWhere('d.forum = :forum')
        ->andWhere('d.titre LIKE :term')
        ->setParameter('forum', $forumId)
        ->setParameter('term', '%' . $term . '%')
        ->orderBy('d.dateCreation', 'DESC')
        ->getQuery()
        ->getResult();
}


    //    /**
    //     * @return Discussion[] Returns an array of Discussion objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Discussion
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
