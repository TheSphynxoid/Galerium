<?php

namespace App\Entity;

use App\Repository\ArtisteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ArtisteRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Artiste
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'artiste')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 160, unique: true)]
    #[Assert\NotBlank(message: "Le slug est obligatoire")]
    #[Assert\Length(
        max: 160,
        maxMessage: "Le slug ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $slug = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: "Le nom d'artiste est obligatoire")]
    #[Assert\Length(
        max: 180,
        maxMessage: "Le nom d'artiste ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $displayName = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(
        max: 2000,
        maxMessage: "La biographie ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $biography = null;

    #[ORM\Column(length: 120, nullable: true)]
    #[Assert\Length(
        max: 120,
        maxMessage: "La spécialité ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $specialty = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $socialLinks = [];

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: "Veuillez saisir une URL valide pour le site web")]
    #[Assert\Length(
        max: 255,
        maxMessage: "L'URL du site ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $website = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le chemin de l'avatar ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $avatarPath = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isFeatured = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(mappedBy: 'artiste', targetEntity: Oeuvre::class, orphanRemoval: true)]
    private Collection $oeuvres;

    


    // Propriétés non mappées pour les réseaux sociaux individuels (pour le formulaire)
    private ?string $facebook = null;

    private ?string $instagram = null;

    private ?string $behance = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->oeuvres = new ArrayCollection();
      
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): static
    {
        $this->displayName = $displayName;
        return $this;
    }

    public function getBiography(): ?string
    {
        return $this->biography;
    }

    public function setBiography(?string $biography): static
    {
        $this->biography = $biography;
        return $this;
    }

    public function getSpecialty(): ?string
    {
        return $this->specialty;
    }

    public function setSpecialty(?string $specialty): static
    {
        $this->specialty = $specialty;
        return $this;
    }

    public function getSocialLinks(): ?array
    {
        return $this->socialLinks;
    }

    public function setSocialLinks(?array $socialLinks): static
    {
        $this->socialLinks = $socialLinks;
        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;
        return $this;
    }

    public function getAvatarPath(): ?string
    {
        return $this->avatarPath;
    }

    public function setAvatarPath(?string $avatarPath): static
    {
        $this->avatarPath = $avatarPath;
        return $this;
    }

    public function isFeatured(): bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getOeuvres(): Collection
    {
        return $this->oeuvres;
    }

    public function addOeuvre(Oeuvre $oeuvre): static
    {
        if (!$this->oeuvres->contains($oeuvre)) {
            $this->oeuvres->add($oeuvre);
            $oeuvre->setArtiste($this);
        }
        return $this;
    }

    public function removeOeuvre(Oeuvre $oeuvre): static
    {
        if ($this->oeuvres->removeElement($oeuvre) && $oeuvre->getArtiste() === $this) {
            $oeuvre->setArtiste(null);
        }
        return $this;
    }

    

    

   

    // Getters et setters pour les réseaux sociaux individuels
    public function getFacebook(): ?string
    {
        return $this->facebook ?? ($this->socialLinks['facebook'] ?? null);
    }

    public function setFacebook(?string $facebook): static
    {
        $this->facebook = $facebook;
        return $this;
    }

    public function getInstagram(): ?string
    {
        return $this->instagram ?? ($this->socialLinks['instagram'] ?? null);
    }

    public function setInstagram(?string $instagram): static
    {
        $this->instagram = $instagram;
        return $this;
    }

    public function getBehance(): ?string
    {
        return $this->behance ?? ($this->socialLinks['behance'] ?? null);
    }

    public function setBehance(?string $behance): static
    {
        $this->behance = $behance;
        return $this;
    }
}