<?php

namespace App\Service;

use App\Entity\Oeuvre;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class OeuvreService
{
    private readonly string $projectDir;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SluggerInterface $slugger,
        ParameterBagInterface $parameterBag,
    ) {
        $this->projectDir = $parameterBag->get('kernel.project_dir');
    }

    public function save(Oeuvre $oeuvre): void
    {
        $oeuvre->setSlug($this->slug($oeuvre->getTitle()));

        if ($oeuvre->getStatus() === Oeuvre::STATUS_PUBLIC && $oeuvre->getPublishedAt() === null) {
            $oeuvre->setPublishedAt(new \DateTimeImmutable());
        }

        // VichUploader gère automatiquement l'upload via setImageFile()
        // L'image est obligatoire si c'est une nouvelle œuvre
        if ($oeuvre->getId() === null && $oeuvre->getImageFile() === null && $oeuvre->getImagePath() === null) {
            throw new LogicException('Une image est requise pour enregistrer une nouvelle œuvre.');
        }

        // Persister l'artiste s'il est nouveau
        $artiste = $oeuvre->getArtiste();
        
        if ($artiste && $artiste->getId() === null) {
            $this->entityManager->persist($artiste);
        }

        // Synchroniser la relation bidirectionnelle
        if ($artiste && !$artiste->getOeuvres()->contains($oeuvre)) {
            $artiste->addOeuvre($oeuvre);
        }

        $this->entityManager->persist($oeuvre);
        $this->entityManager->flush();
    }

    public function delete(Oeuvre $oeuvre): void
    {
        $this->entityManager->remove($oeuvre);
        $this->entityManager->flush();
    }

    private function slug(string $value): string
    {
        return mb_strtolower($this->slugger->slug($value)->toString());
    }
}

