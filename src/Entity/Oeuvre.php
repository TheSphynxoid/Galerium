<?php

namespace App\Entity;

use App\Repository\OeuvreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: OeuvreRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class Oeuvre
{
   public const STATUS_DRAFT = 'draft';
    public const STATUS_PRIVATE = 'private';
    public const STATUS_PUBLIC = 'public';
    public const STATUS_ARCHIVED = 'archived';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'oeuvres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Artiste $artiste = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(
        min: 3,
        max: 180,
        minMessage: "Le titre doit faire au moins {{ limit }} caractères",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $title = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\Length(
        max: 180,
        maxMessage: "Le slug ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "La description est obligatoire")]
    #[Assert\Length(
        min: 10,
        minMessage: "La description doit faire au moins {{ limit }} caractères"
    )]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePath = null;

    #[Vich\UploadableField(mapping: 'oeuvres_images', fileNameProperty: 'imagePath', size: 'imageSize')]
    private ?File $imageFile = null;

    #[ORM\Column(nullable: true)]
    private ?int $imageSize = null;

    #[ORM\Column(length: 40)]
    #[Assert\NotBlank(message: "Le statut est obligatoire")]
    #[Assert\Choice(
        choices: [self::STATUS_DRAFT, self::STATUS_PRIVATE, self::STATUS_PUBLIC, self::STATUS_ARCHIVED],
        message: "Le statut sélectionné n'est pas valide"
    )]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(options: ['default' => true])]
    private bool $isCommentable = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $viewsCount = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $votesCount = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $favoritesCount = 0;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: "Le prix ne peut pas être négatif")]
    private ?float $price = null;

    /**
     * @var Collection<int, Participation>
     */
    #[ORM\OneToMany(targetEntity: Participation::class, mappedBy: 'oeuvre')]
    private Collection $participations;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->participations = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function refreshUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArtiste(): ?Artiste
    {
        return $this->artiste;
    }

    public function setArtiste(?Artiste $artiste): static
    {
        $this->artiste = $artiste;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }


    #[ORM\PrePersist]
    public function generateSlug(): void
    {
        if (empty($this->slug) && !empty($this->title)) {
            // Generate a URL-friendly slug from the title
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $this->title), '-'));
            // Add a unique identifier to ensure uniqueness
            $this->slug = $slug . '-' . uniqid();
        }
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): static
    {
        $this->imagePath = $imagePath;
        return $this;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;
        if (null !== $imageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageSize(?int $imageSize): void
    {
        $this->imageSize = $imageSize;
    }

    public function getImageSize(): ?int
    {
        return $this->imageSize;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function isCommentable(): bool
    {
        return $this->isCommentable;
    }

    public function setIsCommentable(bool $isCommentable): static
    {
        $this->isCommentable = $isCommentable;
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

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    public function incrementViews(): void
    {
        $this->viewsCount++;
    }

    public function setViewsCount(int $viewsCount): static
    {
        $this->viewsCount = $viewsCount;
        return $this;
    }

    public function getViewsCount(): int
    {
        return $this->viewsCount;
    }

    public function setVotesCount(int $votesCount): static
    {
        $this->votesCount = $votesCount;
        return $this;
    }

    public function getVotesCount(): int
    {
        return $this->votesCount;
    }

    public function setFavoritesCount(int $favoritesCount): static
    {
        $this->favoritesCount = $favoritesCount;
        return $this;
    }

    public function getFavoritesCount(): int
    {
        return $this->favoritesCount;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): static
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @return Collection<int, Participation>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(Participation $participation): static
    {
        if (!$this->participations->contains($participation)) {
            $this->participations->add($participation);
            $participation->setOeuvre($this);
        }

        return $this;
    }

    public function removeParticipation(Participation $participation): static
    {
        if ($this->participations->removeElement($participation)) {
            // set the owning side to null (unless already changed)
            if ($participation->getOeuvre() === $this) {
                $participation->setOeuvre(null);
            }
        }

        return $this;
    }
}