<?php
// src/Controller/TranslationController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TranslationController extends AbstractController
{
    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    #[Route('/translate-comment', name: 'app_translate_comment', methods: ['POST'])]
    public function translateComment(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $text = $data['text'] ?? '';
        $target = $data['target'] ?? 'en';
        // Assume French source, or get it from request
        $source = $data['source'] ?? 'fr';

        if (!$text) {
            return $this->json(['error' => 'No text provided'], 400);
        }

        try {
            $response = $this->client->request('GET', 'https://api.mymemory.translated.net/get', [
                'query' => [
                    'q' => $text,
                    'langpair' => $source . '|' . $target,
                ],
                'timeout' => 10,
            ]);

            if ($response->getStatusCode() !== 200) {
                return $this->json(['error' => 'Translation service returned status ' . $response->getStatusCode()], 500);
            }

            $result = $response->toArray();

            if (isset($result['responseData']['translatedText'])) {
                return $this->json(['translatedText' => $result['responseData']['translatedText']]);
            }

            return $this->json(['error' => 'Translation failed - no translated text in response'], 500);
            
        } catch (\Exception $e) {
            error_log('Translation error: ' . $e->getMessage());
            return $this->json(['error' => 'Translation service error: ' . $e->getMessage()], 500);
        }
    }
}