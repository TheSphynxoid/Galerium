<?php

namespace App\Entity;

use App\Repository\ConcoursRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    #[Assert\NotBlank(message: "Le titre est obligatoire.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: "La date de début est obligatoire.")]
    #[Assert\Type(\DateTime::class, message: "La date de début doit être une date valide.")]
    private ?\DateTime $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: "La date de fin est obligatoire.")]
    #[Assert\Type(\DateTime::class, message: "La date de fin doit être une date valide.")]
    #[Assert\GreaterThan(
        propertyPath: "dateDebut",
        message: "La date de fin doit être après la date de début."
    )]
    private ?\DateTime $dateFin = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\NotBlank(message: "Le statut est obligatoire.")]
    #[Assert\Choice(
        choices: ['en_cours', 'termine', 'annule'],
        message: "Le statut doit être 'en_cours', 'termine' ou 'annule'."
    )]
    private ?string $statut = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 2000,
        maxMessage: "Les règles ne peuvent pas dépasser {{ limit }} caractères."
    )]
    private ?string $regles = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $votePublic = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Assert\Type(\DateTime::class, message: "La date de début de vote doit être une date valide.")]
    private ?\DateTime $dateDebutVote = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Assert\Type(\DateTime::class, message: "La date de fin de vote doit être une date valide.")]
    #[Assert\Expression(
        "this.getDateDebutVote() === null or this.getDateFinVote() === null or this.getDateFinVote() > this.getDateDebutVote()",
        message: "La date de fin de vote doit être après la date de début de vote."
    )]
    private ?\DateTime $dateFinVote = null;

    /**
     * @var Collection<int, Utilisateur>
     */
    #[ORM\ManyToMany(targetEntity: Utilisateur::class, inversedBy: 'concours')]
    private Collection $jurys;

    /**
     * @var Collection<int, Participation>
     */
    #[ORM\OneToMany(targetEntity: Participation::class, mappedBy: 'concours', orphanRemoval: true)]
    private Collection $participation;

    public function __construct()
    {
        $this->jurys = new ArrayCollection();
        $this->participation = new ArrayCollection();
    }

    // ======= Getters =======
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

    // ======= Setters =======
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

    /**
     * @return Collection<int, Utilisateur>
     */
    public function getJurys(): Collection
    {
        return $this->jurys;
    }

    public function addJury(Utilisateur $jury): static
    {
        if (!$this->jurys->contains($jury)) {
            $this->jurys->add($jury);
        }

        return $this;
    }

    public function removeJury(Utilisateur $jury): static
    {
        $this->jurys->removeElement($jury);

        return $this;
    }

    /**
     * @return Collection<int, Participation>
     */
    public function getParticipation(): Collection
    {
        return $this->participation;
    }

    public function addParticipation(Participation $participation): static
    {
        if (!$this->participation->contains($participation)) {
            $this->participation->add($participation);
            $participation->setConcours($this);
        }

        return $this;
    }

    public function removeParticipation(Participation $participation): static
    {
        if ($this->participation->removeElement($participation)) {
            // set the owning side to null (unless already changed)
            if ($participation->getConcours() === $this) {
                $participation->setConcours(null);
            }
        }

        return $this;
    }
}
