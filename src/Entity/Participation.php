<?php

namespace App\Entity;

use App\Repository\ParticipationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipationRepository::class)]
class Participation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTime $dateparticipation = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = null;

    #[ORM\Column]
    private ?int $votepublic = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    /**
     * @var Collection<int, Concours>
     */
    #[ORM\ManyToMany(targetEntity: Concours::class, inversedBy: 'participations')]
    private Collection $concours;

    public function __construct()
    {
        $this->concours = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateparticipation(): ?\DateTime
    {
        return $this->dateparticipation;
    }

    public function setDateparticipation(\DateTime $dateparticipation): static
    {
        $this->dateparticipation = $dateparticipation;

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

    public function getVotepublic(): ?int
    {
        return $this->votepublic;
    }

    public function setVotepublic(int $votepublic): static
    {
        $this->votepublic = $votepublic;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
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
}
