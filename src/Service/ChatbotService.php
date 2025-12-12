<?php

namespace App\Service;

use App\Entity\Concours;
use App\Repository\ConcoursRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

class ChatbotService
{
    public function __construct(
        private ConcoursRepository $concoursRepository
    ) {
    }

    /**
     * Traite le message de l'utilisateur et retourne une réponse
     */
    public function processMessage(string $message): array
    {
        $message = strtolower(trim($message));
        
        // Détection de l'intention
        if ($this->isListingAllConcours($message)) {
            return $this->listAllConcours();
        }
        
        if ($this->isListingActiveConcours($message)) {
            return $this->listActiveConcours();
        }
        
        if ($this->isListingClosedConcours($message)) {
            return $this->listClosedConcours();
        }
        
        if ($this->isAskingForDetails($message)) {
            return $this->getConcoursDetails($message);
        }
        
        if ($this->isGreeting($message)) {
            return $this->getGreetingResponse();
        }
        
        if ($this->isAskingForHelp($message)) {
            return $this->getHelpResponse();
        }
        
        // Réponse par défaut
        return [
            'response' => "Je peux vous aider à obtenir des informations sur les concours. Voici ce que je peux faire :\n\n" .
                         "• Lister tous les concours disponibles\n" .
                         "• Afficher les concours actifs\n" .
                         "• Afficher les concours clôturés\n" .
                         "• Donner les détails d'un concours spécifique\n\n" .
                         "Essayez de me poser une question comme : 'Liste tous les concours' ou 'Quels sont les concours actifs ?'",
            'type' => 'text'
        ];
    }

    /**
     * Vérifie si l'utilisateur demande la liste de tous les concours
     */
    private function isListingAllConcours(string $message): bool
    {
        $keywords = ['tous les concours', 'liste des concours', 'concours disponibles', 'tous concours', 'lister concours'];
        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Vérifie si l'utilisateur demande les concours actifs
     */
    private function isListingActiveConcours(string $message): bool
    {
        $keywords = ['concours actifs', 'concours actif', 'actifs', 'en cours', 'ouvert'];
        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Vérifie si l'utilisateur demande les concours clôturés
     */
    private function isListingClosedConcours(string $message): bool
    {
        $keywords = ['concours clôturés', 'concours clôturé', 'clôturés', 'clôturé', 'terminés', 'terminé', 'fermés', 'fermé'];
        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Vérifie si l'utilisateur demande des détails sur un concours
     */
    private function isAskingForDetails(string $message): bool
    {
        $keywords = ['détails', 'détail', 'information', 'infos', 'concours', 'id'];
        $hasKeyword = false;
        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                $hasKeyword = true;
                break;
            }
        }
        
        // Vérifie s'il y a un numéro dans le message (ID potentiel)
        if ($hasKeyword && preg_match('/\d+/', $message, $matches)) {
            return true;
        }
        
        return false;
    }

    /**
     * Vérifie si c'est une salutation
     */
    private function isGreeting(string $message): bool
    {
        $greetings = ['bonjour', 'bonsoir', 'salut', 'hello', 'hi', 'bonne journée', 'coucou'];
        foreach ($greetings as $greeting) {
            if (str_contains($message, $greeting)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Vérifie si l'utilisateur demande de l'aide
     */
    private function isAskingForHelp(string $message): bool
    {
        $keywords = ['aide', 'help', 'assistance', 'que peux-tu', 'peux-tu', 'comment'];
        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Liste tous les concours
     */
    private function listAllConcours(): array
    {
        $concours = $this->concoursRepository->findAll();
        
        if (empty($concours)) {
            return [
                'response' => "Il n'y a actuellement aucun concours disponible.",
                'type' => 'text'
            ];
        }
        
        $response = "Voici tous les concours disponibles (" . count($concours) . ") :\n\n";
        
        foreach ($concours as $c) {
            $response .= "📌 **Concours #" . $c->getId() . " : " . $c->getTitre() . "**\n";
            $response .= "   Statut : " . $c->getStatut() . "\n";
            $response .= "   Date de début : " . $c->getDateDebut()->format('d/m/Y H:i') . "\n";
            $response .= "   Date de fin : " . $c->getDateFin()->format('d/m/Y H:i') . "\n\n";
        }
        
        return [
            'response' => $response,
            'type' => 'text',
            'data' => $concours
        ];
    }

    /**
     * Liste les concours actifs
     */
    private function listActiveConcours(): array
    {
        // Note: Le repository utilise 'actif' en minuscule, mais l'entité stocke 'Actif' avec majuscule
        $concours = $this->concoursRepository->createQueryBuilder('c')
            ->where('c.statut = :statut')
            ->setParameter('statut', 'Actif')
            ->orderBy('c.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
        
        if (empty($concours)) {
            return [
                'response' => "Il n'y a actuellement aucun concours actif.",
                'type' => 'text'
            ];
        }
        
        $response = "Voici les concours actifs (" . count($concours) . ") :\n\n";
        
        foreach ($concours as $c) {
            $response .= "✅ **Concours #" . $c->getId() . " : " . $c->getTitre() . "**\n";
            $response .= "   Description : " . substr($c->getDescription(), 0, 100) . "...\n";
            $response .= "   Date de début : " . $c->getDateDebut()->format('d/m/Y H:i') . "\n";
            $response .= "   Date de fin : " . $c->getDateFin()->format('d/m/Y H:i') . "\n";
            $response .= "   Pour plus de détails, demandez : 'Détails du concours #" . $c->getId() . "'\n\n";
        }
        
        return [
            'response' => $response,
            'type' => 'text',
            'data' => $concours
        ];
    }

    /**
     * Liste les concours clôturés
     */
    private function listClosedConcours(): array
    {
        $concours = $this->concoursRepository->createQueryBuilder('c')
            ->where('c.statut = :statut')
            ->setParameter('statut', 'cloturé')
            ->orderBy('c.dateFin', 'DESC')
            ->getQuery()
            ->getResult();
        
        if (empty($concours)) {
            return [
                'response' => "Il n'y a actuellement aucun concours clôturé.",
                'type' => 'text'
            ];
        }
        
        $response = "Voici les concours clôturés (" . count($concours) . ") :\n\n";
        
        foreach ($concours as $c) {
            $response .= "🔒 **Concours #" . $c->getId() . " : " . $c->getTitre() . "**\n";
            $response .= "   Date de début : " . $c->getDateDebut()->format('d/m/Y H:i') . "\n";
            $response .= "   Date de fin : " . $c->getDateFin()->format('d/m/Y H:i') . "\n\n";
        }
        
        return [
            'response' => $response,
            'type' => 'text',
            'data' => $concours
        ];
    }

    /**
     * Récupère les détails d'un concours spécifique
     */
    private function getConcoursDetails(string $message): array
    {
        // Extrait l'ID du concours du message
        preg_match('/\d+/', $message, $matches);
        
        if (empty($matches)) {
            return [
                'response' => "Je n'ai pas pu identifier l'ID du concours. Veuillez spécifier un numéro, par exemple : 'Détails du concours #1'",
                'type' => 'text'
            ];
        }
        
        $id = (int) $matches[0];
        $concours = $this->concoursRepository->find($id);
        
        if (!$concours) {
            return [
                'response' => "Le concours #" . $id . " n'existe pas.",
                'type' => 'text'
            ];
        }
        
        $response = "📋 **Détails du Concours #" . $concours->getId() . "**\n\n";
        $response .= "**Titre :** " . $concours->getTitre() . "\n\n";
        $response .= "**Description :**\n" . $concours->getDescription() . "\n\n";
        $response .= "**Statut :** " . $concours->getStatut() . "\n";
        $response .= "**Date de début :** " . $concours->getDateDebut()->format('d/m/Y à H:i') . "\n";
        $response .= "**Date de fin :** " . $concours->getDateFin()->format('d/m/Y à H:i') . "\n";
        
        if ($concours->getRegles()) {
            $response .= "\n**Règles :**\n" . $concours->getRegles() . "\n";
        }
        
        if ($concours->getDateDebutVote()) {
            $response .= "\n**Période de vote :**\n";
            $response .= "   Début : " . $concours->getDateDebutVote()->format('d/m/Y à H:i') . "\n";
            if ($concours->getDateFinVote()) {
                $response .= "   Fin : " . $concours->getDateFinVote()->format('d/m/Y à H:i') . "\n";
            }
        }
        
        $response .= "\n**Vote public :** " . ($concours->isVotePublic() ? 'Oui' : 'Non') . "\n";
        
        $participations = $concours->getParticipations();
        $response .= "\n**Nombre de participations :** " . $participations->count() . "\n";
        
        return [
            'response' => $response,
            'type' => 'text',
            'data' => $concours
        ];
    }

    /**
     * Réponse de salutation
     */
    private function getGreetingResponse(): array
    {
        return [
            'response' => "Bonjour ! 👋\n\nJe suis votre assistant virtuel pour les concours. Je peux vous aider à :\n\n" .
                         "• Lister tous les concours disponibles\n" .
                         "• Afficher les concours actifs\n" .
                         "• Afficher les concours clôturés\n" .
                         "• Donner les détails d'un concours spécifique\n\n" .
                         "Comment puis-je vous aider aujourd'hui ?",
            'type' => 'text'
        ];
    }

    /**
     * Réponse d'aide
     */
    private function getHelpResponse(): array
    {
        return [
            'response' => "🤖 **Aide - Chatbot Concours**\n\n" .
                         "Je peux répondre à vos questions sur les concours. Voici quelques exemples :\n\n" .
                         "📋 **Lister les concours :**\n" .
                         "   • 'Liste tous les concours'\n" .
                         "   • 'Quels sont les concours disponibles ?'\n\n" .
                         "✅ **Concours actifs :**\n" .
                         "   • 'Quels sont les concours actifs ?'\n" .
                         "   • 'Affiche les concours en cours'\n\n" .
                         "🔒 **Concours clôturés :**\n" .
                         "   • 'Quels sont les concours clôturés ?'\n" .
                         "   • 'Affiche les concours terminés'\n\n" .
                         "📋 **Détails d'un concours :**\n" .
                         "   • 'Détails du concours #1'\n" .
                         "   • 'Informations sur le concours 2'\n\n" .
                         "N'hésitez pas à me poser vos questions !",
            'type' => 'text'
        ];
    }
}




