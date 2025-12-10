<?php

namespace App\Entity;

use App\Repository\CommissionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommissionRepository::class)]
class Commission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Oeuvre $oeuvre = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Artiste $artiste = null;

    #[ORM\Column]
    private float $montant = 0;

    #[ORM\Column]
    private float $prixFinal = 0;

    #[ORM\Column]
    private \DateTimeImmutable $dateCreation;

    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getOeuvre(): ?Oeuvre { return $this->oeuvre; }
    public function setOeuvre(?Oeuvre $oeuvre): static { $this->oeuvre = $oeuvre; return $this; }

    public function getArtiste(): ?Artiste { return $this->artiste; }
    public function setArtiste(?Artiste $artiste): static { $this->artiste = $artiste; return $this; }

    public function getMontant(): float { return $this->montant; }
    public function setMontant(float $montant): static { $this->montant = $montant; return $this; }

    public function getPrixFinal(): float { return $this->prixFinal; }
    public function setPrixFinal(float $prixFinal): static { $this->prixFinal = $prixFinal; return $this; }

    public function getDateCreation(): \DateTimeImmutable { return $this->dateCreation; }
}
