<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ConcoursRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ConcoursRepository::class)]
class Concours
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private ?int $id = null;

	#[ORM\Column(type: 'string', length: 255)]
	#[Assert\NotBlank]
	private ?string $titre = null;

	#[ORM\Column(type: 'text', nullable: true)]
	private ?string $description = null;

	#[ORM\Column(type: 'datetime_immutable')]
	#[Assert\NotNull]
	private ?\DateTimeImmutable $dateDebut = null;

	#[ORM\Column(type: 'datetime_immutable')]
	#[Assert\NotNull]
	private ?\DateTimeImmutable $dateFin = null;

	#[ORM\Column(type: 'boolean')]
	private bool $actif = true;

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getTitre(): ?string
	{
		return $this->titre;
	}

	public function setTitre(string $titre): static
	{
		$this->titre = $titre;
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

	public function getDateDebut(): ?\DateTimeImmutable
	{
		return $this->dateDebut;
	}

	public function setDateDebut(\DateTimeImmutable $dateDebut): static
	{
		$this->dateDebut = $dateDebut;
		return $this;
	}

	public function getDateFin(): ?\DateTimeImmutable
	{
		return $this->dateFin;
	}

	public function setDateFin(\DateTimeImmutable $dateFin): static
	{
		$this->dateFin = $dateFin;
		return $this;
	}

	public function isActif(): bool
	{
		$now = new \DateTimeImmutable();
		return $this->actif && $now >= $this->dateDebut && $now <= $this->dateFin;
	}

	public function setActif(bool $actif): static
	{
		$this->actif = $actif;
		return $this;
	}
}





