<?php

namespace App\Repository;

use App\Entity\Artiste;
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
     * @return Oeuvre[]
     */
    public function search(?string $term = null, ?string $categorySlug = null, ?string $status = null, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('o')
            ->addSelect('a')
            ->leftJoin('o.artiste', 'a')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($term) {
            $qb->andWhere('LOWER(o.title) LIKE :term OR LOWER(o.description) LIKE :term')
                ->setParameter('term', '%'.mb_strtolower($term).'%');
        }

        if ($categorySlug) {
            $qb->leftJoin('o.categories', 'c')
                ->andWhere('c.slug = :slug')
                ->setParameter('slug', $categorySlug);
        }

        if ($status) {
            $qb->andWhere('o.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Oeuvre[]
     */
    public function findByArtiste(Artiste $artiste, ?string $status = null, ?string $term = null, ?string $categorySlug = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->andWhere('o.artiste = :artiste')
            ->setParameter('artiste', $artiste)
            ->orderBy('o.createdAt', 'DESC');

        if ($status) {
            $qb->andWhere('o.status = :status')
                ->setParameter('status', $status);
        }

        if ($term) {
            $qb->andWhere('LOWER(o.title) LIKE :term OR LOWER(o.description) LIKE :term')
                ->setParameter('term', '%'.mb_strtolower($term).'%');
        }

        if ($categorySlug) {
            $qb->leftJoin('o.categories', 'c')
                ->andWhere('c.slug = :slug')
                ->setParameter('slug', $categorySlug);
        }

        return $qb->getQuery()->getResult();
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

    /**
     * Find oeuvres not in any enchere
     */
    public function findNotInEnchere()
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('App\Entity\Enchere', 'e', 'WITH', 'e.oeuvre = o.id')
            ->where('e.id IS NULL')
            ->orderBy('o.createdAt', 'DESC');
    }

    public function findAvailableForEnchere(?int $enchereId = null)
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('App\Entity\Enchere', 'e', 'WITH', 'e.oeuvre = o')
            ->orderBy('o.createdAt', 'DESC');

        if ($enchereId !== null) {
            $qb->andWhere('e.id IS NULL OR e.id = :enchereId')
                ->setParameter('enchereId', $enchereId);
        } else {
            $qb->andWhere('e.id IS NULL');
        }

        return $qb;
    }
}
