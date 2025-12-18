<?php

namespace App\Service;

class BadWordsService
{
    /**
     * Liste des mots inappropriés (vous pouvez l'étendre ou la charger depuis un fichier/config)
     */
    private array $badWords = [
        'merde',
        'putain',
        'connard',
        'salope',
        'enculé',
        'foutre',
        'bordel',
        'con',
        'pute',
        'bite',
        'chier',
        'nique',
        'pd',
        'fdp',
        'tg',
        'ntm',
        'ptn',
        
    ];

    /**
     * Charge les mots inappropriés depuis un fichier (optionnel)
     * Format: un mot par ligne
     */
    public function loadFromFile(string $filePath): void
    {
        if (file_exists($filePath) && is_readable($filePath)) {
            $words = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($words as $word) {
                $word = trim($word);
                if (!empty($word) && !in_array(mb_strtolower($word, 'UTF-8'), $this->badWords)) {
                    $this->badWords[] = mb_strtolower($word, 'UTF-8');
                }
            }
        }
    }

    /**
     * Vérifie si le texte contient des mots inappropriés
     * 
     * @param string $text Le texte à vérifier
     * @return array Liste des mots inappropriés trouvés (vide si aucun)
     */
    public function checkBadWords(string $text): array
    {
        $foundWords = [];
        $textLower = mb_strtolower($text, 'UTF-8');
        
        // Normaliser le texte (supprimer les accents pour une meilleure détection)
        $textNormalized = $this->normalizeText($textLower);
        
        foreach ($this->badWords as $badWord) {
            $badWordLower = mb_strtolower($badWord, 'UTF-8');
            $badWordNormalized = $this->normalizeText($badWordLower);
            
            // Vérifier si le mot est présent dans le texte
            if (mb_strpos($textNormalized, $badWordNormalized) !== false) {
                $foundWords[] = $badWord;
            }
        }
        
        return array_unique($foundWords);
    }

    /**
     * Normalise le texte en supprimant les accents et caractères spéciaux
     * pour une meilleure détection des mots inappropriés
     */
    private function normalizeText(string $text): string
    {
        // Supprimer les accents
        $text = str_replace(
            ['à', 'á', 'â', 'ã', 'ä', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ò', 'ó', 'ô', 'õ', 'ö', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'ç'],
            ['a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'c'],
            $text
        );
        
        // Supprimer les caractères spéciaux qui pourraient être utilisés pour contourner le filtre
        $text = preg_replace('/[^a-z0-9\s]/i', '', $text);
        
        return $text;
    }

    /**
     * Ajoute un mot à la liste des mots inappropriés
     */
    public function addBadWord(string $word): void
    {
        if (!in_array(mb_strtolower($word, 'UTF-8'), $this->badWords)) {
            $this->badWords[] = mb_strtolower($word, 'UTF-8');
        }
    }

    /**
     * Retourne la liste complète des mots inappropriés
     */
    public function getBadWords(): array
    {
        return $this->badWords;
    }
}

