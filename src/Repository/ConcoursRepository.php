<?php

namespace App\Repository;

use App\Entity\Concours;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Concours>
 */
class ConcoursRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Concours::class);
    }

    /**
     * Sauvegarde un concours en base de données
     */
    public function save(Concours $concours, bool $flush = false): void
    {
        $this->getEntityManager()->persist($concours);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime un concours de la base de données
     */
    public function remove(Concours $concours, bool $flush = false): void
    {
        $this->getEntityManager()->remove($concours);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve tous les concours actifs
     */
    public function findActifs(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.statut = :statut')
            ->setParameter('statut', 'actif')
            ->orderBy('c.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les concours clôturés
     */
    public function findClotures(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.statut = :statut')
            ->setParameter('statut', 'clôturé')
            ->orderBy('c.dateFin', 'DESC')
            ->getQuery()
            ->getResult();
    }
}


