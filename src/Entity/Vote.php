<?php

namespace App\Entity;

use App\Repository\VoteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VoteRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_visiteur_concours', columns: ['visiteur_id', 'concours_id'])]
class Vote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $visiteur = null;

    #[ORM\ManyToOne(targetEntity: Participation::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Participation $participation = null;

    #[ORM\ManyToOne(targetEntity: Concours::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Concours $concours = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $dateVote = null;

    public function __construct()
    {
        $this->dateVote = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVisiteur(): ?Utilisateur
    {
        return $this->visiteur;
    }

    public function setVisiteur(?Utilisateur $visiteur): static
    {
        $this->visiteur = $visiteur;
        return $this;
    }

    public function getParticipation(): ?Participation
    {
        return $this->participation;
    }

    public function setParticipation(?Participation $participation): static
    {
        $this->participation = $participation;
        return $this;
    }

    public function getConcours(): ?Concours
    {
        return $this->concours;
    }

    public function setConcours(?Concours $concours): static
    {
        $this->concours = $concours;
        return $this;
    }

    public function getDateVote(): ?\DateTime
    {
        return $this->dateVote;
    }

    public function setDateVote(\DateTime $dateVote): static
    {
        $this->dateVote = $dateVote;
        return $this;
    }
}
