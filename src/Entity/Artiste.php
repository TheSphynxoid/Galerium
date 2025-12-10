<?php

namespace App\Entity;

use App\Repository\ArtisteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: ArtisteRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class Artiste
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'artiste')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $user = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: "Le nom d'artiste est obligatoire")]
    #[Assert\Length(
        min: 2,
        max: 180,
        minMessage: "Le nom d'artiste doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le nom d'artiste ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $displayName = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\NotBlank(message: "La biographie est obligatoire")]
    #[Assert\Length(
     min: 10,
        max: 2000,
        maxMessage: "La biographie ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $biography = null;

    #[ORM\Column(length: 120, nullable: true)]
    #[Assert\NotBlank(message: "La spécialité est obligatoire")]
    #[Assert\Length(
        max: 120,
        maxMessage: "La spécialité ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $specialty = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $socialLinks = [];

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le site web est obligatoire")]
    #[Assert\Url(message: "Veuillez saisir une URL valide pour le site web")]
    #[Assert\Regex(
        pattern: '/^https?:\/\/.+/',
        message: "L'URL doit commencer par http:// ou https://"
    )]
    #[Assert\Length(
        max: 255,
        maxMessage: "L'URL du site ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $website = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatarPath = null;

    #[Vich\UploadableField(mapping: 'artiste_avatars', fileNameProperty: 'avatarPath', size: 'imageSize')]
    #[Assert\File(
        maxSize: '5M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
        mimeTypesMessage: 'Veuillez télécharger une image valide (JPEG, PNG, WebP).'
    )]
    private ?File $imageFile = null;

    #[ORM\Column(nullable: true)]
    private ?int $imageSize = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isFeatured = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(mappedBy: 'artiste', targetEntity: Oeuvre::class, orphanRemoval: true)]
    private Collection $oeuvres;

    // Réseaux sociaux (facultatif mais validé en URL)
    #[Assert\Url(message: "Veuillez saisir une URL valide pour Facebook")]
    #[Assert\Regex(
        pattern: '/^https?:\/\/.+/',
        message: "L'URL Facebook doit commencer par http:// ou https://"
    )]
    private ?string $facebook = null;

    #[Assert\Url(message: "Veuillez saisir une URL valide pour Instagram")]
    #[Assert\Regex(
        pattern: '/^https?:\/\/.+/',
        message: "L'URL Instagram doit commencer par http:// ou https://"
    )]
    private ?string $instagram = null;

    #[Assert\Url(message: "Veuillez saisir une URL valide pour Behance")]
    #[Assert\Regex(
        pattern: '/^https?:\/\/.+/',
        message: "L'URL Behance doit commencer par http:// ou https://"
    )]
    private ?string $behance = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->oeuvres = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    #[ORM\PrePersist]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
        $this->updateSocialLinks();
    }

    public function updateSocialLinks(): void
    {
        $this->socialLinks = [
            'facebook' => $this->facebook,
            'instagram' => $this->instagram,
            'behance' => $this->behance,
        ];
    }

    // === Getters & Setters ===

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?Utilisateur { return $this->user; }

    public function setUser(?Utilisateur $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getDisplayName(): ?string { return $this->displayName; }

    public function setDisplayName(string $displayName): static
    {
        $this->displayName = $displayName;
        return $this;
    }

    public function getBiography(): ?string { return $this->biography; }

    public function setBiography(?string $biography): static
    {
        $this->biography = $biography;
        return $this;
    }

    public function getSpecialty(): ?string { return $this->specialty; }

    public function setSpecialty(?string $specialty): static
    {
        $this->specialty = $specialty;
        return $this;
    }

    public function getSocialLinks(): ?array { return $this->socialLinks; }

    public function setSocialLinks(?array $socialLinks): static
    {
        $this->socialLinks = $socialLinks;
        return $this;
    }

    public function getWebsite(): ?string { return $this->website; }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;
        return $this;
    }

    public function getAvatarPath(): ?string { return $this->avatarPath; }

    public function setAvatarPath(?string $avatarPath): static
    {
        $this->avatarPath = $avatarPath;
        return $this;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;
        if (null !== $imageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File { return $this->imageFile; }

    public function setImageSize(?int $imageSize): void { $this->imageSize = $imageSize; }

    public function getImageSize(): ?int { return $this->imageSize; }

    public function isFeatured(): bool { return $this->isFeatured; }

    public function setIsFeatured(bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    public function getOeuvres(): Collection { return $this->oeuvres; }

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

    public function setFacebook(?string $facebook): static { $this->facebook = $facebook; return $this; }

    public function getInstagram(): ?string { return $this->instagram ?? ($this->socialLinks['instagram'] ?? null); }

    public function setInstagram(?string $instagram): static { $this->instagram = $instagram; return $this; }

    public function getBehance(): ?string { return $this->behance ?? ($this->socialLinks['behance'] ?? null); }

    public function setBehance(?string $behance): static { $this->behance = $behance; return $this; }
    #[ORM\Column(options: ['default' => 0])]
    private int $viewsCount = 0;

    public function getViewsCount(): int { return $this->viewsCount; }

    public function setViewsCount(int $viewsCount): static { $this->viewsCount = $viewsCount; return $this; }

    public function incrementViews(): static
    {
        $this->viewsCount++;
        return $this;
    }
}