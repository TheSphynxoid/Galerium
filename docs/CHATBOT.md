# 🤖 Chatbot IA - Documentation

## Vue d'ensemble

Le chatbot IA a été intégré dans l'application Galerium pour fournir des informations sur les concours de manière interactive. Le chatbot peut répondre aux questions des utilisateurs concernant les concours disponibles, leur statut et leurs détails.

## Fonctionnalités

Le chatbot peut répondre aux questions suivantes :

### 1. Lister tous les concours
- **Exemples de questions :**
  - "Liste tous les concours"
  - "Quels sont les concours disponibles ?"
  - "Affiche tous les concours"

### 2. Afficher les concours actifs
- **Exemples de questions :**
  - "Quels sont les concours actifs ?"
  - "Affiche les concours en cours"
  - "Concours actifs"

### 3. Afficher les concours clôturés
- **Exemples de questions :**
  - "Quels sont les concours clôturés ?"
  - "Affiche les concours terminés"
  - "Concours clôturés"

### 4. Obtenir les détails d'un concours spécifique
- **Exemples de questions :**
  - "Détails du concours #1"
  - "Informations sur le concours 2"
  - "Concours #3"

## Architecture

### Service : `ChatbotService`
- **Fichier :** `src/Service/ChatbotService.php`
- **Responsabilité :** Traite les messages de l'utilisateur, détecte l'intention et génère les réponses appropriées
- **Méthodes principales :**
  - `processMessage(string $message): array` - Point d'entrée principal
  - `listAllConcours()` - Liste tous les concours
  - `listActiveConcours()` - Liste les concours actifs
  - `listClosedConcours()` - Liste les concours clôturés
  - `getConcoursDetails(string $message)` - Récupère les détails d'un concours

### Contrôleur : `ChatbotController`
- **Fichier :** `src/Controller/ChatbotController.php`
- **Route API :** `/chatbot/message` (POST)
- **Responsabilité :** Reçoit les requêtes HTTP et retourne les réponses JSON

### Interface Frontend
- **Fichier :** `templates/chatbot/widget.html.twig`
- **Intégration :** Inclus dans `templates/base.html.twig`
- **Fonctionnalités :**
  - Widget flottant en bas à droite de l'écran
  - Interface de chat interactive
  - Communication AJAX avec l'API

## Utilisation

### Pour les utilisateurs

1. Cliquez sur l'icône du chatbot en bas à droite de l'écran
2. Tapez votre question dans le champ de saisie
3. Appuyez sur Entrée ou cliquez sur le bouton d'envoi
4. Le chatbot répondra avec les informations demandées

### Pour les développeurs

#### Ajouter de nouvelles fonctionnalités

1. **Ajouter une nouvelle intention dans `ChatbotService` :**
   ```php
   private function isNewIntention(string $message): bool
   {
       $keywords = ['mot-clé1', 'mot-clé2'];
       foreach ($keywords as $keyword) {
           if (str_contains($message, $keyword)) {
               return true;
           }
       }
       return false;
   }
   ```

2. **Ajouter le traitement dans `processMessage` :**
   ```php
   if ($this->isNewIntention($message)) {
       return $this->handleNewIntention($message);
   }
   ```

3. **Implémenter la méthode de traitement :**
   ```php
   private function handleNewIntention(string $message): array
   {
       // Votre logique ici
       return [
           'response' => 'Réponse à l\'utilisateur',
           'type' => 'text'
       ];
   }
   ```

## Intégration avec des modèles de langage avancés

Pour améliorer la compréhension du chatbot, vous pouvez intégrer une API de langage comme OpenAI :

1. **Installer le package HTTP Client de Symfony** (déjà inclus)
2. **Ajouter votre clé API dans `.env` :**
   ```
   OPENAI_API_KEY=votre_clé_api
   ```

3. **Modifier `ChatbotService` pour utiliser l'API :**
   ```php
   use Symfony\Contracts\HttpClient\HttpClientInterface;

   public function __construct(
       private ConcoursRepository $concoursRepository,
       private HttpClientInterface $httpClient,
       private string $openaiApiKey
   ) {
   }
   ```

## Format de réponse

Le chatbot retourne des réponses au format JSON :

```json
{
    "success": true,
    "response": "Texte de la réponse",
    "type": "text",
    "data": null
}
```

- `success` : Indique si la requête a réussi
- `response` : Le texte de la réponse à afficher
- `type` : Type de réponse (actuellement toujours "text")
- `data` : Données supplémentaires (optionnel)

## Personnalisation

### Modifier l'apparence

Éditez le fichier `templates/chatbot/widget.html.twig` pour modifier :
- Les couleurs (dans la section `<style>`)
- La position du widget
- Les icônes et emojis

### Modifier les réponses

Éditez les méthodes dans `ChatbotService.php` pour personnaliser les messages du chatbot.

## Tests

Pour tester le chatbot :

1. Démarrez votre serveur Symfony
2. Accédez à n'importe quelle page de l'application
3. Cliquez sur l'icône du chatbot
4. Testez les différentes questions mentionnées ci-dessus

## Améliorations futures

- [ ] Intégration avec OpenAI GPT ou un modèle de langage similaire
- [ ] Support multilingue
- [ ] Historique des conversations
- [ ] Suggestions de questions
- [ ] Support des images et fichiers
- [ ] Analyse de sentiment
- [ ] Statistiques d'utilisation






