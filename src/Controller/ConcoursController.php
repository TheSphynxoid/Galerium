<?php

namespace App\Controller;

use App\Entity\Concours;
use App\Form\ConcoursType;
use App\Repository\ConcoursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/concours')]
final class ConcoursController extends AbstractController
{
    #[Route(name: 'app_concours_index', methods: ['GET'])]
    public function index(ConcoursRepository $concoursRepository): Response
    {
        return $this->render('concours/index.html.twig', [
            'concours' => $concoursRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_concours_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $concour = new Concours();
        $form = $this->createForm(ConcoursType::class, $concour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($concour);
            $entityManager->flush();

            return $this->redirectToRoute('app_concours_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('concours/new.html.twig', [
            'concour' => $concour,
            'form' => $form,
        ]);
    }

    #[Route('/{id<\d+>}', name: 'app_concours_show', methods: ['GET'])]
    public function show(Concours $concour): Response
    {
        return $this->render('concours/show.html.twig', [
            'concour' => $concour,
        ]);
    }

    #[Route('/{id<\d+>}/edit', name: 'app_concours_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Concours $concour, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ConcoursType::class, $concour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_concours_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('concours/edit.html.twig', [
            'concour' => $concour,
            'form' => $form,
        ]);
    }

    #[Route('/{id<\d+>}', name: 'app_concours_delete', methods: ['POST'])]
    public function delete(Request $request, Concours $concour, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$concour->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($concour);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_concours_index', [], Response::HTTP_SEE_OTHER);
    }

#[Route('/user', name: 'app_concours_user_index', methods: ['GET'])]
public function userIndex(ConcoursRepository $concoursRepository): Response
{
    // Ici tu peux filtrer les concours si nécessaire, par exemple uniquement ceux qui sont actifs
    $concours = $concoursRepository->findAll(); 

    return $this->render('concours/user_index.html.twig', [
        'concours' => $concours,
    ]);
}









}
