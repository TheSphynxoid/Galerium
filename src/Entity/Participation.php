<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ParticipationRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParticipationRepository::class)]
class Participation
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private ?int $id = null;

	#[ORM\ManyToOne(targetEntity: Artiste::class)]
	#[ORM\JoinColumn(nullable: false)]
	private ?Artiste $artiste = null;

	#[ORM\ManyToOne(targetEntity: Oeuvre::class)]
	#[ORM\JoinColumn(nullable: true)]
	private ?Oeuvre $oeuvre = null;

	#[ORM\Column(type: 'integer', options: ['default' => 0])]
	private int $votesPublic = 0;

	#[ORM\Column(type: 'integer', nullable: true)]
	private ?int $noteJury = null;

	#[ORM\Column(type: 'datetime_immutable')]
	private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'participation')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Concours $concours = null;

	public function __construct()
	{
		$this->createdAt = new \DateTimeImmutable();
		$this->votesPublic = 0;
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getArtiste(): ?Artiste
	{
		return $this->artiste;
	}

	public function setArtiste(Artiste $artiste): static
	{
		$this->artiste = $artiste;
		return $this;
	}
	public function getOeuvre(): ?Oeuvre
	{
		return $this->oeuvre;
	}

	public function setOeuvre(?Oeuvre $oeuvre): static
	{
		$this->oeuvre = $oeuvre;
		return $this;
	}

	public function getVotesPublic(): int
	{
		return $this->votesPublic;
	}

	public function setVotesPublic(int $votesPublic): static
	{
		$this->votesPublic = $votesPublic;
		return $this;
	}

	public function incrementVotesPublic(): static
	{
		$this->votesPublic++;
		return $this;
	}

	public function getNoteJury(): ?int
	{
		return $this->noteJury;
	}

	public function setNoteJury(?int $noteJury): static
	{
		$this->noteJury = $noteJury;
		return $this;
	}

	public function getCreatedAt(): ?\DateTimeImmutable
	{
		return $this->createdAt;
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
}





