<?php

namespace App\Controller;

use App\Entity\Concours;
use App\Entity\Report;
use App\Entity\Utilisateur;
use App\Form\ReportType;
use App\Repository\ConcoursRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\ReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    private UtilisateurRepository $userRepository;

    public function __construct(UtilisateurRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    // -------------------------
    // Dashboard admin
    // -------------------------
    #[Route('/', name: 'app_admin')]
    public function dashboard(Request $request): Response
    {
        $session = $request->getSession();

        if (!$session->has('user_id') || $session->get('user_role') !== 'ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->userRepository->find($session->get('user_id'));

        if (!$user) {
            $session->clear();
            return $this->redirectToRoute('app_login');
        }

        $users = $this->userRepository->findAll();

        return $this->render('admin/dashboard.html.twig', [
            'user' => $user,
            'users' => $users,
        ]);
    }

    // -------------------------
    // Associer des jurys à un concours
    // -------------------------
    #[Route('/concours/{id}/jurys', name: 'admin_concours_jurys')]
    public function assignJurys(
        Concours $concours,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $session = $request->getSession();
        if (!$session->has('user_id') || $session->get('user_role') !== 'ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createFormBuilder($concours)
            ->add('jurys', EntityType::class, [
                'class' => Utilisateur::class,
                'choice_label' => fn (Utilisateur $user) => sprintf('%s (%s)', $user->getFullName(), $user->getEmail()),
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'query_builder' => fn (UtilisateurRepository $repo) => $repo
                    ->createQueryBuilder('u')
                    ->where('u.role = :role')
                    ->setParameter('role', 'JURY')
                    ->orderBy('u.nom', 'ASC'),
                'label' => 'Jurys',
                'help' => 'Sélectionnez les membres du jury pour ce concours.',
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Jurys mis à jour pour ce concours.');

            return $this->redirectToRoute('app_concours_show', ['id' => $concours->getId()]);
        }

        return $this->render('admin/concours_jurys.html.twig', [
            'concours' => $concours,
            'form' => $form->createView(),
        ]);
    }

    // -------------------------
    // Signaler un utilisateur
    // -------------------------
    #[Route('/report/{id}', name: 'admin_report_user')]
    public function reportUser(Utilisateur $user, Request $request, EntityManagerInterface $em): Response
    {
        $session = $request->getSession();
        if (!$session->has('user_id') || $session->get('user_role') !== 'ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $admin = $this->userRepository->find($session->get('user_id'));

        $report = new Report();
        $report->setReportedUser($user);
        $report->setReportedBy($admin);

        $form = $this->createForm(ReportType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Persister le signalement
            $em->persist($report);

            // Désactiver l'utilisateur signalé
            $user->setIsActive(false);

            $em->flush();

            $this->addFlash('success', 'Utilisateur signalé et désactivé avec succès.');
            return $this->redirectToRoute('app_admin');
        }

        return $this->render('admin/report_user.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    // -------------------------
    // Liste des signalements
    // -------------------------
    #[Route('/reports', name: 'admin_reports_list')]
    public function listReports(ReportRepository $repo, Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->has('user_id') || $session->get('user_role') !== 'ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $reports = $repo->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/reports_list.html.twig', [
            'reports' => $reports
        ]);
    }

    // -------------------------
    // Supprimer un utilisateur
    // -------------------------
    #[Route('/delete/{id}', name: 'admin_delete_user', methods: ['POST'])]
    public function deleteUser(Utilisateur $user, Request $request, EntityManagerInterface $em): Response
    {
        $session = $request->getSession();
        if (!$session->has('user_id') || $session->get('user_role') !== 'ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        if ($this->isCsrfTokenValid('delete_user'.$user->getId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();

            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin');
    }
}
