<?php

namespace App\Entity;

use App\Repository\ConcoursRepository;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConcoursRepository::class)]
class Concours
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $dateFin = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private ?string $statut = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $regles = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $votePublic = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $dateDebutVote = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $dateFinVote = null;

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDateDebut(): ?\DateTime
    {
        return $this->dateDebut;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->dateFin;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function getRegles(): ?string
    {
        return $this->regles;
    }

    public function isVotePublic(): bool
    {
        return $this->votePublic;
    }

    public function getDateDebutVote(): ?\DateTime
    {
        return $this->dateDebutVote;
    }

    public function getDateFinVote(): ?\DateTime
    {
        return $this->dateFinVote;
    }

    // Setters
    public function setTitre(?string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setDateDebut(?\DateTime $dateDebut): self
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function setDateFin(?\DateTime $dateFin): self
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function setStatut(?string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function setRegles(?string $regles): self
    {
        $this->regles = $regles;
        return $this;
    }

    public function setVotePublic(bool $votePublic): self
    {
        $this->votePublic = $votePublic;
        return $this;
    }

    public function setDateDebutVote(?\DateTime $dateDebutVote): self
    {
        $this->dateDebutVote = $dateDebutVote;
        return $this;
    }

    public function setDateFinVote(?\DateTime $dateFinVote): self
    {
        $this->dateFinVote = $dateFinVote;
        return $this;
    }
}

