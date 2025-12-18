<?php

namespace App\Controller;

use App\Entity\Forum;
use App\Repository\DiscussionRepository;
use App\Form\ForumType;
use App\Repository\ForumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\TwilioService;  

#[Route('/forum')]
final class ForumController extends AbstractController
{
    #[Route(name: 'app_forum_index', methods: ['GET'])]
    public function index(ForumRepository $forumRepository): Response
    {
        return $this->render('forum/index.html.twig', [
            'forums' => $forumRepository->findAll(),
        ]);
    }



#[Route('/new', name: 'app_forum_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager, TwilioService $twilio): Response
{
    $forum = new Forum();

    $form = $this->createForm(ForumType::class, $forum);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($forum);
        $entityManager->flush();

        // Send SMS notification
        $message = "A new forum '{$forum->getTitre()}' was created on your website!";
        $twilio->sendSms($_ENV['TWILIO_TO'], $message);

        return $this->redirectToRoute('app_forum_index', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('forum/new.html.twig', [
        'forum' => $forum,
        'form' => $form->createView(), 
    ]);
}









    #[Route('/search', name: 'app_forum_search', methods: ['GET'])]
    public function search(Request $request, ForumRepository $forumRepository): JsonResponse
    {
        $term = $request->query->get('q', '');

        if ($term === '') {
            $forums = $forumRepository->findAll();
        } else {
            $forums = $forumRepository->searchByTitleOrDescription($term);
        }

        $data = [];
        foreach ($forums as $forum) {
            $data[] = [
                'id' => $forum->getId(),
                'titre' => $forum->getTitre(),
                'description' => $forum->getDescription(),
                'dateCreation' => $forum->getDateCreation() ? $forum->getDateCreation()->format('Y-m-d H:i:s') : '',
                'statut' => $forum->getStatut(),
                'categorie' => $forum->getCategorie(),
            ];
        }

        return new JsonResponse($data);
    }



    #[Route('/{id}/discussion-search', name: 'app_forum_discussion_search', methods: ['GET'])]
    public function searchDiscussions(
        int $id,
        Request $request,
        DiscussionRepository $discussionRepository
    ): JsonResponse {
        $term = $request->query->get('q', '');

        if ($term === '') {
            $discussions = $discussionRepository->findBy(
                ['forum' => $id],
                ['dateCreation' => 'DESC']
            );
        } else {
            $discussions = $discussionRepository->searchByForumAndTitle($id, $term);
        }

        $data = [];
        foreach ($discussions as $discussion) {
            $data[] = [
                'id' => $discussion->getId(),
                'titre' => $discussion->getTitre(),
                'dateCreation' => $discussion->getDateCreation() ? $discussion->getDateCreation()->format('Y-m-d H:i') : '',
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/{id}', name: 'app_forum_show', methods: ['GET'])]
    public function show(Request $request, Forum $forum, DiscussionRepository $discussionRepository): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 10;

        $qb = $discussionRepository->createQueryBuilder('d')
            ->andWhere('d.forum = :forum')
            ->setParameter('forum', $forum)
            ->orderBy('d.dateCreation', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($qb);
        $total = count($paginator);
        $pages = (int) max(1, ceil($total / $perPage));

        return $this->render('forum/show.html.twig', [
            'forum' => $forum,
            'discussions' => iterator_to_array($paginator),
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_forum_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Forum $forum, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ForumType::class, $forum);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_forum_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('forum/edit.html.twig', [
            'forum' => $forum,
            'form' => $form->createView(), 
        ]);
    }

    #[Route('/{id}', name: 'app_forum_delete', methods: ['POST'])]
    public function delete(Request $request, Forum $forum, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$forum->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($forum);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_forum_index', [], Response::HTTP_SEE_OTHER);
    }
}