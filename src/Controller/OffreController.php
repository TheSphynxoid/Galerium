<?php

namespace App\Controller;

use App\Entity\Offre;
use App\Form\OffreType;
use App\Repository\OffreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/offre')]
final class OffreController extends AbstractController
{
    #[Route(name: 'app_offre_index', methods: ['GET'])]
    public function index(OffreRepository $offreRepository): Response
    {
        return $this->render('offre/index.html.twig', [
            'offres' => $offreRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_offre_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $offre = new Offre();
        $form = $this->createForm(OffreType::class, $offre);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $entityManager->persist($offre);
            $this->ValidateOffer($form, $offre);
            if($form->isValid()){
                $entityManager->flush();
                return $this->redirectToRoute('app_offre_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('offre/new.html.twig', [
            'offre' => $offre,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_offre_show', methods: ['GET'])]
    public function show(Offre $offre): Response
    {
        return $this->render('offre/show.html.twig', [
            'offre' => $offre,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_offre_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Offre $offre, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OffreType::class, $offre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->ValidateOffer($form, $offre);
            if($form->isValid()){
                $entityManager->flush();
                return $this->redirectToRoute('app_offre_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('offre/edit.html.twig', [
            'offre' => $offre,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_offre_delete', methods: ['POST'])]
    public function delete(Request $request, Offre $offre, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$offre->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($offre);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_offre_index', [], Response::HTTP_SEE_OTHER);
    }

    private function ValidateOffer(FormInterface $form, Offre $offre)
    {
        if($offre->getMontant() <= 0){
            $form->addError(new FormError('Le montant de l\'offre doit être supérieur à zéro.'));
            return false;
        }
        if($offre->getMontant() <= $offre->getEchere()->getPrixActuel()){
            $form->get('montant')->addError(new FormError('Le montant de l\'offre doit être supérieur au prix actuel de l\'enchère.'));
            return false;
        }
        if($offre->getEchere()->getStatut() !== \App\Enum\EnchereStatut::ACTIVE){
            $form->addError(new FormError('Vous ne pouvez pas faire une offre sur une enchère qui n\'est pas en cours.'));
            return false;
        }
        if($offre->getEchere()->getDateFin() < new \DateTime()){
            $form->addError(new FormError('Vous ne pouvez pas faire une offre sur une enchère terminée.'));
            return false;
        }
        return true;
    }
}
