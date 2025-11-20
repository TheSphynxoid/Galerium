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
	 * @return Concours[]
	 */
	public function findActifs(): array
	{
		$now = new \DateTimeImmutable();

		return $this->createQueryBuilder('c')
			->andWhere('c.actif = :actif')
			->andWhere('c.dateDebut <= :now')
			->andWhere('c.dateFin >= :now')
			->setParameter('actif', true)
			->setParameter('now', $now)
			->orderBy('c.dateDebut', 'DESC')
			->getQuery()
			->getResult();
	}
}





