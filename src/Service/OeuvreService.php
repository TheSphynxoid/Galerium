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

    public function save(Oeuvre $oeuvre, ?UploadedFile $imageFile = null): void
    {
        error_log("=== OeuvreService::save START ===");
        error_log("Oeuvre ID: " . ($oeuvre->getId() ?? 'NULL (new)'));
        error_log("Oeuvre title: " . ($oeuvre->getTitle() ?? 'NULL'));
        
        $oeuvre->setSlug($this->slug($oeuvre->getTitle()));
        error_log("Slug set: " . $oeuvre->getSlug());

        if ($oeuvre->getStatus() === Oeuvre::STATUS_PUBLIC && $oeuvre->getPublishedAt() === null) {
            $oeuvre->setPublishedAt(new \DateTimeImmutable());
            error_log("Published date set");
        }

        if ($imageFile !== null) {
            error_log("Image file provided: " . $imageFile->getClientOriginalName());
            $oeuvre->setImagePath($this->uploadImage($imageFile, $oeuvre->getSlug()));
            error_log("Image uploaded: " . $oeuvre->getImagePath());
        }

        // L'image est obligatoire si c'est une nouvelle œuvre
        if ($oeuvre->getId() === null && $oeuvre->getImagePath() === null) {
            error_log("ERROR: No image for new artwork");
            throw new LogicException('Une image est requise pour enregistrer une nouvelle œuvre.');
        }

        // Persister l'artiste s'il est nouveau (pas encore dans la BD)
        $artiste = $oeuvre->getArtiste();
        error_log("Artiste: " . ($artiste ? $artiste->getDisplayName() : 'NULL'));
        error_log("Artiste ID: " . ($artiste && $artiste->getId() ? $artiste->getId() : 'NULL (new)'));
        
        if ($artiste && $artiste->getId() === null) {
            error_log("Persisting new artiste");
            $this->entityManager->persist($artiste);
        }

        // Synchroniser la relation bidirectionnelle
        // Important : ajouter l'œuvre à la collection de l'artiste
        if ($artiste && !$artiste->getOeuvres()->contains($oeuvre)) {
            error_log("Adding oeuvre to artiste collection");
            $artiste->addOeuvre($oeuvre);
        }

        error_log("Persisting oeuvre");
        $this->entityManager->persist($oeuvre);
        
        error_log("Flushing to database");
        $this->entityManager->flush();
        
        error_log("=== OeuvreService::save SUCCESS ===");
        error_log("Oeuvre saved with ID: " . $oeuvre->getId());
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

    private function uploadImage(UploadedFile $file, string $slug): string
    {
        $uploadsDir = $this->projectDir.'/public/uploads/oeuvres';

        // Créer le dossier s'il n'existe pas
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0775, true);
        }

        // Générer un nom de fichier unique
        $filename = sprintf('%s-%s.%s', $slug, uniqid(), $file->guessExtension() ?? 'jpg');
        
        try {
            $file->move($uploadsDir, $filename);
        } catch (\Exception $e) {
            throw new LogicException('Erreur lors de l\'upload de l\'image : '.$e->getMessage());
        }

        return 'uploads/oeuvres/'.$filename;
    }
}

