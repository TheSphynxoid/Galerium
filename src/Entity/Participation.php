<?php

namespace App\Entity;

use App\Repository\ParticipationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipationRepository::class)]
class Participation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTime $dateParticipation = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = null;

    #[ORM\Column(nullable: true)]
    private ?float $noteFinale = null;

    #[ORM\Column(nullable: true)]
    private ?int $scoreVote = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * @var Collection<int, Concours>
     */
    #[ORM\ManyToMany(targetEntity: Concours::class, inversedBy: 'participations')]
    private Collection $concours;

    #[ORM\OneToOne(inversedBy: 'participation', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Oeuvre $oeuvre = null;

    public function __construct()
    {
        $this->concours = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateParticipation(): ?\DateTime
    {
        return $this->dateParticipation;
    }

    public function setDateParticipation(\DateTime $dateParticipation): static
    {
        $this->dateParticipation = $dateParticipation;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getNoteFinale(): ?float
    {
        return $this->noteFinale;
    }

    public function setNoteFinale(?float $noteFinale): static
    {
        $this->noteFinale = $noteFinale;

        return $this;
    }

    public function getScoreVote(): ?int
    {
        return $this->scoreVote;
    }

    public function setScoreVote(?int $scoreVote): static
    {
        $this->scoreVote = $scoreVote;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, Concours>
     */
    public function getConcours(): Collection
    {
        return $this->concours;
    }

    public function addConcour(Concours $concour): static
    {
        if (!$this->concours->contains($concour)) {
            $this->concours->add($concour);
        }

        return $this;
    }

    public function removeConcour(Concours $concour): static
    {
        $this->concours->removeElement($concour);

        return $this;
    }

    public function getOeuvre(): ?Oeuvre
    {
        return $this->oeuvre;
    }

    public function setOeuvre(Oeuvre $oeuvre): static
    {
        $this->oeuvre = $oeuvre;

        return $this;
    }
}
