<?php

namespace App\Entity;

use App\Repository\FavoriRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FavoriRepository::class)]
class Favori
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTime $dateAjout = null;

    #[ORM\ManyToOne(inversedBy: 'favoris')]
    private ?oeuvre $oeuvre = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateAjout(): ?\DateTime
    {
        return $this->dateAjout;
    }

    public function setDateAjout(\DateTime $dateAjout): static
    {
        $this->dateAjout = $dateAjout;

        return $this;
    }

    public function getOeuvre(): ?oeuvre
    {
        return $this->oeuvre;
    }

    public function setOeuvre(?oeuvre $oeuvre): static
    {
        $this->oeuvre = $oeuvre;

        return $this;
    }
}
