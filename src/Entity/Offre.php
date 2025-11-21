<?php

namespace App\Entity;

use App\Repository\OffreRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OffreRepository::class)]
class Offre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $montant = null;

    #[ORM\Column]
    private ?\DateTime $dateOffre = null;

    #[ORM\ManyToOne(inversedBy: 'offre')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Enchere $echere = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMontant(): ?float
    {
        return $this->montant;
    }

    public function setMontant(float $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getDateOffre(): ?\DateTime
    {
        return $this->dateOffre;
    }

    public function setDateOffre(\DateTime $dateOffre): static
    {
        $this->dateOffre = $dateOffre;

        return $this;
    }

    public function getEchere(): ?Enchere
    {
        return $this->echere;
    }

    public function setEchere(?Enchere $echere): static
    {
        $this->echere = $echere;

        return $this;
    }
}
