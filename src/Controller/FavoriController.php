<?php

namespace App\Controller;

use App\Entity\Favori;
use App\Form\FavoriType;
use App\Repository\FavoriRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/favori')]
final class FavoriController extends AbstractController
{
    #[Route(name: 'app_favori_index', methods: ['GET'])]
    public function index(FavoriRepository $favoriRepository): Response
    {
        return $this->render('favori/index.html.twig', [
            'favoris' => $favoriRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_favori_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $favori = new Favori();
        $form = $this->createForm(FavoriType::class, $favori);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($favori);
            $entityManager->flush();

            return $this->redirectToRoute('app_favori_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('favori/new.html.twig', [
            'favori' => $favori,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_favori_show', methods: ['GET'])]
    public function show(Favori $favori): Response
    {
        return $this->render('favori/show.html.twig', [
            'favori' => $favori,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_favori_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Favori $favori, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FavoriType::class, $favori);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_favori_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('favori/edit.html.twig', [
            'favori' => $favori,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_favori_delete', methods: ['POST'])]
    public function delete(Request $request, Favori $favori, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$favori->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($favori);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_favori_index', [], Response::HTTP_SEE_OTHER);
    }
}
