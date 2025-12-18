<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 */
#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
class UtilisateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    public function searchAndFilter(?string $search = null, ?string $role = null, ?string $sortBy = 'id', ?string $sortOrder = 'ASC'): QueryBuilder
    {
        $qb = $this->createQueryBuilder('u');

        if ($search) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('u.email', ':search'),
                    $qb->expr()->like('u.nom', ':search'),
                    $qb->expr()->like('u.prenom', ':search')
                )
            )
            ->setParameter('search', '%' . $search . '%');
        }

        if ($role) {
            $qb->andWhere('u.role = :role')
               ->setParameter('role', $role);
        }

        $allowedSortFields = ['id', 'email', 'nom', 'prenom', 'role', 'dateInscription'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'id';
        }

        $sortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';

        $qb->orderBy('u.' . $sortBy, $sortOrder);

        return $qb;
    }
}
