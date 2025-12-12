<?php

namespace App\Controller;

use App\Service\ChatbotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/chatbot')]
class ChatbotController extends AbstractController
{
    public function __construct(
        private ChatbotService $chatbotService
    ) {
    }

    /**
     * Endpoint API pour recevoir les messages du chatbot
     */
    #[Route('/message', name: 'app_chatbot_message', methods: ['POST'])]
    public function message(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $message = $data['message'] ?? '';

        if (empty($message)) {
            return new JsonResponse([
                'success' => false,
                'response' => 'Veuillez fournir un message.'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->chatbotService->processMessage($message);
            
            return new JsonResponse([
                'success' => true,
                'response' => $result['response'],
                'type' => $result['type'] ?? 'text',
                'data' => $result['data'] ?? null
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'response' => 'Une erreur est survenue : ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Page de test du chatbot (optionnel)
     */
    #[Route('/test', name: 'app_chatbot_test', methods: ['GET'])]
    public function test(): Response
    {
        return $this->render('chatbot/test.html.twig');
    }
}




