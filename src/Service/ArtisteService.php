<?php

namespace App\Service;

use App\Entity\Artiste;
use Doctrine\ORM\EntityManagerInterface;

class ArtisteService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Artiste $artiste): void
    {
        $this->entityManager->persist($artiste);
        $this->entityManager->flush();
    }
}
