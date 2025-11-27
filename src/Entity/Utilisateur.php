<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'utilisateur')] // Make sure table name matches your DB
class Utilisateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'email', length: 100, unique: true)]
    private ?string $email = null;

    #[ORM\Column(name: 'password', length: 30)]
    private ?string $password = null;

    #[ORM\Column(name: 'nom', length: 30)]
    private ?string $nom = null;

    #[ORM\Column(name: 'prenom', length: 30)]
    private ?string $prenom = null;

    #[ORM\Column(name: 'role', length: 30)]
    private ?string $role = 'VISITEUR';

    #[ORM\Column(name: 'date_inscription', type: 'datetime')]
    private ?\DateTimeInterface $dateInscription = null;

    /**
     * @var Collection<int, Commentaire>
     */
    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'owner', orphanRemoval: true)]
    private Collection $commentaires;

    /**
     * @var Collection<int, Concours>
     */
    #[ORM\ManyToMany(targetEntity: Concours::class, mappedBy: 'jurys')]
    private Collection $concours;

    public function __construct()
    {
        $this->commentaires = new ArrayCollection();
        $this->concours = new ArrayCollection();
    }

    // Getters and Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getDateInscription(): ?\DateTimeInterface
    {
        return $this->dateInscription;
    }

    public function setDateInscription(\DateTimeInterface $dateInscription): self
    {
        $this->dateInscription = $dateInscription;
        return $this;
    }

    // Helper method to get full name
    public function getFullName(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    /**
     * @return Collection<int, Commentaire>
     */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): static
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires->add($commentaire);
            $commentaire->setOwner($this);
        }

        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): static
    {
        if ($this->commentaires->removeElement($commentaire)) {
            // set the owning side to null (unless already changed)
            if ($commentaire->getOwner() === $this) {
                $commentaire->setOwner(null);
            }
        }

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
            $concour->addJury($this);
        }

        return $this;
    }

    public function removeConcour(Concours $concour): static
    {
        if ($this->concours->removeElement($concour)) {
            $concour->removeJury($this);
        }

        return $this;
    }
}