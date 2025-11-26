<?php

namespace App\Controller;

use App\Entity\Enchere;
use App\Enum\EnchereStatut;
use App\Form\EnchereType;
use App\Repository\EnchereRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/enchere')]
final class EnchereController extends AbstractController
{
    #[Route(name: 'app_enchere_index', methods: ['GET'])]
    public function index(EnchereRepository $enchereRepository): Response
    {
        return $this->render('enchere/index.html.twig', [
            'encheres' => $enchereRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_enchere_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $enchere = new Enchere();
        $form = $this->createForm(EnchereType::class, $enchere);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $entityManager->persist($enchere);
            $this->ValidateEnchere($form, $enchere);
            if($form->isValid()){
                $enchere->setDateDebut(new \DateTime());
                $enchere->setPrixActuel($enchere->getPrixDeBase());
                $enchere->setStatut(EnchereStatut::ACTIVE);
                $entityManager->flush();
                return $this->redirectToRoute('app_enchere_index', [], Response::HTTP_SEE_OTHER);
            }

        }

        return $this->render('enchere/new.html.twig', [
            'enchere' => $enchere,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_enchere_show', methods: ['GET'])]
    public function show(Enchere $enchere): Response
    {
        return $this->render('enchere/show.html.twig', [
            'enchere' => $enchere,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_enchere_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Enchere $enchere, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EnchereType::class, $enchere);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->ValidateEnchere($form, $enchere);
            if($form->isValid()){
                $entityManager->flush();
    
                return $this->redirectToRoute('app_enchere_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('enchere/edit.html.twig', [
            'enchere' => $enchere,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_enchere_delete', methods: ['POST'])]
    public function delete(Request $request, Enchere $enchere, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$enchere->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($enchere);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_enchere_index', [], Response::HTTP_SEE_OTHER);
    }

    private function ValidateEnchere(FormInterface $form, Enchere $enchere)
    {
        if($enchere->getDateFin() <= new \DateTime()) {
            $form->get('dateFin')->addError(new FormError('La date de fin doit être postérieure à la date de début.'));
            return false;
        }
        return true;
    }
}
