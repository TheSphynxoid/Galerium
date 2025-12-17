<?php

namespace App\Entity;

use App\Repository\ReportRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Utilisateur;

#[ORM\Entity(repositoryClass: ReportRepository::class)]
class Report
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $reportedUser = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $reportedBy = null;

    #[ORM\Column(type: 'text')]
    private ?string $reason = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // ----------- GETTERS & SETTERS -----------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReportedUser(): ?Utilisateur
    {
        return $this->reportedUser;
    }

    public function setReportedUser(?Utilisateur $reportedUser): self
    {
        $this->reportedUser = $reportedUser;
        return $this;
    }

    public function getReportedBy(): ?Utilisateur
    {
        return $this->reportedBy;
    }

    public function setReportedBy(?Utilisateur $reportedBy): self
    {
        $this->reportedBy = $reportedBy;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
