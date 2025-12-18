<?php

namespace App\Entity;

use App\Entity\Concours;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: 'utilisateur')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    // =========================
    // FIELDS
    // =========================

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[Assert\NotBlank(message: "L'email est obligatoire.")]
    #[Assert\Email(message: "L'email n'est pas valide.")]
    #[Assert\Regex(pattern: '/\.com$/i', message: "L'email doit se terminer par .com.")]
    #[Assert\Length(max: 100)]
    #[ORM\Column(name: 'email', length: 100, unique: true)]
    private ?string $email = null;

    #[Assert\Length(min: 8, minMessage: "Le mot de passe doit contenir au moins 8 caractères.")]
    #[ORM\Column(name: 'password', length: 255, nullable: true)]
    private ?string $password = null;

    #[Assert\NotBlank(message: "Le nom est obligatoire.")]
    #[Assert\Length(max: 20)]
    #[ORM\Column(name: 'nom', length: 30)]
    private ?string $nom = null;

    #[Assert\NotBlank(message: "Le prénom est obligatoire.")]
    #[Assert\Length(max: 20)]
    #[ORM\Column(name: 'prenom', length: 30)]
    private ?string $prenom = null;

    #[Assert\NotBlank(message: "Le rôle est obligatoire.")]
    #[Assert\Choice(choices: ['VISITEUR', 'ARTISTE'], message: "Le rôle doit être VISITEUR ou ARTISTE.")]
    #[ORM\Column(name: 'role', length: 30)]
    private ?string $role = 'VISITEUR';

    #[ORM\Column(name: 'date_inscription', type: 'datetime')]
    private ?\DateTimeInterface $dateInscription = null;

    #[ORM\Column(name: 'google_id', length: 255, nullable: true, unique: true)]
    private ?string $googleId = null;

    #[ORM\Column(name: 'reset_code', length: 255, nullable: true)]
    private ?string $resetCode = null;

    #[ORM\Column(name: 'reset_code_expires_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetCodeExpiresAt = null;

    #[ORM\Column(name: 'verification_code', type: 'string', length: 6, nullable: true)]
    private ?string $verificationCode = null;

    #[ORM\Column(name: 'verification_code_expires_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $verificationCodeExpiresAt = null;

    #[ORM\Column(name: 'is_verified', type: 'boolean', options: ['default' => false])]
    private bool $isVerified = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(name: 'avatar_url', type: 'string', length: 255, nullable: true)]
    private ?string $avatarUrl = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Artiste::class, cascade: ['persist', 'remove'])]
    private ?Artiste $artiste = null;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Commentaire::class, orphanRemoval: true)]
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

    // =========================
    // GETTERS & SETTERS
    // =========================

    public function getId(): ?int { return $this->id; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): self { $this->prenom = $prenom; return $this; }

    public function getRole(): ?string { return $this->role; }
    public function setRole(string $role): self { $this->role = $role; return $this; }

    public function getDateInscription(): ?\DateTimeInterface { return $this->dateInscription; }
    public function setDateInscription(\DateTimeInterface $date): self { $this->dateInscription = $date; return $this; }

    public function getGoogleId(): ?string { return $this->googleId; }
    public function setGoogleId(?string $googleId): self { $this->googleId = $googleId; return $this; }

    public function getResetCode(): ?string { return $this->resetCode; }
    public function setResetCode(?string $code): self { $this->resetCode = $code; return $this; }

    public function getResetCodeExpiresAt(): ?\DateTimeInterface { return $this->resetCodeExpiresAt; }
    public function setResetCodeExpiresAt(?\DateTimeInterface $date): self { $this->resetCodeExpiresAt = $date; return $this; }

    public function getVerificationCode(): ?string { return $this->verificationCode; }
    public function setVerificationCode(?string $code): self { $this->verificationCode = $code; return $this; }

    public function getVerificationCodeExpiresAt(): ?\DateTimeInterface { return $this->verificationCodeExpiresAt; }
    public function setVerificationCodeExpiresAt(?\DateTimeInterface $date): self { $this->verificationCodeExpiresAt = $date; return $this; }

    public function isVerified(): bool { return $this->isVerified; }
    public function setIsVerified(bool $v): self { $this->isVerified = $v; return $this; }

    public function getIsActive(): bool { return $this->isActive; }
    public function setIsActive(bool $active): self { $this->isActive = $active; return $this; }

    public function getAvatarUrl(): ?string { return $this->avatarUrl; }
    public function setAvatarUrl(?string $url): self { $this->avatarUrl = $url; return $this; }

    /**
     * @return Collection<int, Concours>
     */
    public function getConcours(): Collection
    {
        return $this->concours;
    }

    public function addConcour(Concours $concour): self
    {
        if (!$this->concours->contains($concour)) {
            $this->concours->add($concour);
            $concour->addJury($this);
        }

        return $this;
    }

    public function removeConcour(Concours $concour): self
    {
        if ($this->concours->removeElement($concour)) {
            $concour->removeJury($this);
        }

        return $this;
    }

    // =========================
    // 🔒 LOGIQUE SIGNALÉ (AJOUT)
    // =========================

    public function isSignaled(): bool
    {
        // signalé = désactivé
        return !$this->isActive;
    }

    public function setIsSignaled(bool $signaled): self
    {
        $this->isActive = !$signaled;
        return $this;
    }

    // =========================
    // SECURITY INTERFACE
    // =========================

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        if (str_starts_with($this->role, 'ROLE_')) {
            return [$this->role];
        }
        return ['ROLE_' . strtoupper($this->role)];
    }

    public function eraseCredentials(): void {}

    public function getFullName(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }
}
