<?php

namespace App\Controller;

use App\Entity\Concours;
use App\Entity\Participation;
use App\Form\SoumissionParticipationType;
use App\Repository\ConcoursRepository;
use App\Repository\OeuvreRepository;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ParticipationController extends AbstractController
{
	#[Route('/artiste/concours', name: 'app_concours_actifs')]
	public function listerConcours(ConcoursRepository $concoursRepository, ParticipationRepository $participationRepository): Response
	{
		$this->denyAccessUnlessGranted('ROLE_ARTISTE');

		/** @var \App\Entity\Artiste $artiste */
		$artiste = $this->getUser();
		$concours = $concoursRepository->findActifs();

		// Marquer ceux où l'artiste est déjà inscrit
		$partByConcoursId = [];
		foreach ($concours as $c) {
			$part = $participationRepository->findOneByArtisteAndConcours($artiste, $c);
			$partByConcoursId[$c->getId()] = $part;
		}

		return $this->render('concours/index.html.twig', [
			'concours' => $concours,
			'participations' => $partByConcoursId,
		]);
	}

	#[Route('/artiste/concours/{id}/participer', name: 'app_participer_concours', requirements: ['id' => '\d+'])]
	public function participer(
		Concours $concours,
		ParticipationRepository $participationRepository,
		EntityManagerInterface $entityManager
	): Response {
		$this->denyAccessUnlessGranted('ROLE_ARTISTE');

		/** @var \App\Entity\Artiste $artiste */
		$artiste = $this->getUser();

		if (!$concours->isActif()) {
			$this->addFlash('warning', 'Ce concours n\'est pas actif.');
			return $this->redirectToRoute('app_concours_actifs');
		}

		$existante = $participationRepository->findOneByArtisteAndConcours($artiste, $concours);
		if ($existante) {
			$this->addFlash('info', 'Vous êtes déjà inscrit à ce concours.');
			return $this->redirectToRoute('app_concours_actifs');
		}

		$participation = (new Participation())
			->setArtiste($artiste)
			->setConcours($concours);

		$entityManager->persist($participation);
		$entityManager->flush();

		$this->addFlash('success', 'Inscription au concours réussie. Vous pouvez maintenant soumettre une œuvre.');
		return $this->redirectToRoute('app_concours_actifs');
	}

	#[Route('/artiste/concours/{id}/soumettre', name: 'app_soumettre_oeuvre_concours', requirements: ['id' => '\d+'])]
	public function soumettreOeuvre(
		Request $request,
		Concours $concours,
		OeuvreRepository $oeuvreRepository,
		ParticipationRepository $participationRepository,
		EntityManagerInterface $entityManager
	): Response {
		$this->denyAccessUnlessGranted('ROLE_ARTISTE');

		/** @var \App\Entity\Artiste $artiste */
		$artiste = $this->getUser();

		if (!$concours->isActif()) {
			$this->addFlash('warning', 'Ce concours n\'est pas actif.');
			return $this->redirectToRoute('app_concours_actifs');
		}

		$participation = $participationRepository->findOneByArtisteAndConcours($artiste, $concours);
		if (!$participation) {
			$this->addFlash('warning', 'Inscrivez-vous au concours avant de soumettre une œuvre.');
			return $this->redirectToRoute('app_concours_actifs');
		}

		$oeuvres = $oeuvreRepository->findByArtiste($artiste);

		$form = $this->createForm(SoumissionParticipationType::class, null, [
			'oeuvres' => $oeuvres,
		]);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$oeuvre = $form->get('oeuvre')->getData();
			$participation->setOeuvre($oeuvre);
			$entityManager->flush();

			$this->addFlash('success', 'Œuvre soumise au concours avec succès !');
			return $this->redirectToRoute('app_concours_actifs');
		}

		return $this->render('participation/soumettre.html.twig', [
			'concours' => $concours,
			'form' => $form,
		]);
	}
}





