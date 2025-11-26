<?php

namespace App\Repository;

use App\Entity\Artiste;
use App\Entity\Concours;
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

	public function findOneByArtisteAndConcours(Artiste $artiste, Concours $concours): ?Participation
	{
		return $this->createQueryBuilder('p')
			->andWhere('p.artiste = :artiste')
			->andWhere('p.concours = :concours')
			->setParameter('artiste', $artiste)
			->setParameter('concours', $concours)
			->getQuery()
			->getOneOrNullResult();
	}

	/**
	 * @return Participation[]
	 */
	public function findByArtiste(Artiste $artiste): array
	{
		return $this->createQueryBuilder('p')
			->andWhere('p.artiste = :artiste')
			->setParameter('artiste', $artiste)
			->orderBy('p.createdAt', 'DESC')
			->getQuery()
			->getResult();
	}
}





