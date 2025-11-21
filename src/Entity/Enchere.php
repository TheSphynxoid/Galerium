<?php

namespace App\Entity;

use App\Enum\EnchereStatut;
use App\Repository\EnchereRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EnchereRepository::class)]
class Enchere
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: false)]
    #[Assert\NotBlank(message: "Le montant de base ne peut pas être vide.")]
    #[Assert\Positive(message: "Le montant doit être supérieur à zéro.")]
    private ?float $prixDeBase = null;

    #[ORM\Column]
    private ?float $prixActuel = null;

    #[ORM\Column]
    private ?\DateTime $dateDebut = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $dateFin = null;

    #[ORM\Column(enumType: EnchereStatut::class)]
    private ?EnchereStatut $Statut = null;

    /**
     * @var Collection<int, Offre>
     */
    #[ORM\OneToMany(targetEntity: Offre::class, mappedBy: 'echere', orphanRemoval: true)]
    private Collection $offre;

    public function __construct()
    {
        $this->offre = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrixDeBase(): ?float
    {
        return $this->prixDeBase;
    }

    public function setPrixDeBase(float $prixDeBase): static
    {
        $this->prixDeBase = $prixDeBase;

        return $this;
    }

    public function getPrixActuel(): ?float
    {
        return $this->prixActuel;
    }

    public function setPrixActuel(float $prixActuel): static
    {
        $this->prixActuel = $prixActuel;

        return $this;
    }

    public function getDateDebut(): ?\DateTime
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTime $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTime $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getStatut(): ?EnchereStatut
    {
        return $this->Statut;
    }

    public function setStatut(EnchereStatut $Statut): static
    {
        $this->Statut = $Statut;

        return $this;
    }

    /**
     * @return Collection<int, Offre>
     */
    public function getOffre(): Collection
    {
        return $this->offre;
    }

    public function addOffre(Offre $offre): static
    {
        if (!$this->offre->contains($offre)) {
            $this->offre->add($offre);
            $offre->setEchere($this);
        }

        return $this;
    }

    public function removeOffre(Offre $offre): static
    {
        if ($this->offre->removeElement($offre)) {
            // set the owning side to null (unless already changed)
            if ($offre->getEchere() === $this) {
                $offre->setEchere(null);
            }
        }

        return $this;
    }
}
