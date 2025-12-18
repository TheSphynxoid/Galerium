<?php

namespace App\Controller;

use App\Entity\Discussion;
use App\Entity\Utilisateur;
use App\Form\DiscussionType;
use App\Repository\ForumRepository;

use App\Repository\DiscussionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\CommentaireRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\JsonResponse;



#[Route('/discussion')]
final class DiscussionController extends AbstractController
{
    #[Route(name: 'app_discussion_index', methods: ['GET'])]
    public function index(DiscussionRepository $discussionRepository): Response
    {
        return $this->render('discussion/index.html.twig', [
            'discussions' => $discussionRepository->findAll(),
        ]);
    }




#[Route('/comment/report', name: 'app_comment_report', methods: ['POST'])]
public function reportComment(Request $request, MailerInterface $mailer, CommentaireRepository $repo): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    $comment = $repo->find($data['commentId'] ?? 0);

    if (!$comment) {
        return $this->json(['success' => false]);
    }

    // Get the owner of the comment
    $owner = $comment->getOwner(); // assuming you have getOwner() returning Utilisateur
    $ownerEmail = $owner?->getEmail(); // PHP 8 null-safe operator

    // Fallback to admin if owner has no email
    $toEmail = $ownerEmail ?: 'admin@forum.com';

    $ownerName = $owner?->getFullName() ?: 'User';
    $commentId = $comment->getId();
    $reason = $data['reason'];
    
    $email = (new Email())
        ->from('noreply@forum.com')
        ->to($toEmail)
        ->subject('Comment Report')
        ->text("Hello {$ownerName},\n\nYour comment #{$commentId} was reported.\nReason: {$reason}");

    $mailer->send($email);

    return $this->json(['success' => true]);
}

#[Route('/new', name: 'app_discussion_new', methods: ['GET', 'POST'])]
public function new(
    Request $request, 
    EntityManagerInterface $entityManager,
    ForumRepository $forumRepository,
    UtilisateurRepository $userRepository

): Response {

    $session = $request->getSession();

    if (!$session->has('user_id')) {
        return $this->redirectToRoute('app_login');
    }

    $user = $userRepository->find($session->get('user_id'));

    if (!$user instanceof Utilisateur) {
        // not logged in
        return $this->redirectToRoute('app_login');
    }

    $discussion = new Discussion();
    $discussion->setStatut('active');
    $discussion->setOwner($user);
    // Get forum id from query parameter
    $forumId = $request->query->get('forum');
    $forum = null;
    if ($forumId) {
        $forum = $forumRepository->find($forumId);
        if ($forum) {
            $discussion->setForum($forum);
        }
    }

    $form = $this->createForm(DiscussionType::class, $discussion);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Set forum explicitly to avoid tampering
        if ($forum) {
            $discussion->setForum($forum);
        }

        // Set creation date and statut automatically
        $discussion->setDateCreation(new \DateTime());
        $discussion->setStatut('active');

        // Optional: set owner if user is logged in
        $user = $this->getUser();
        if ($user) {
            $discussion->setOwner($user);
        }

        $entityManager->persist($discussion);
        $entityManager->flush();

        // Redirect back to forum show page to see new discussion
        return $this->redirectToRoute('app_forum_show', ['id' => $forum?->getId()]);
    }

    return $this->render('discussion/new.html.twig', [
        'discussion' => $discussion,
        'form' => $form,
        'forum' => $forum,
    ]);
}















#[Route('/{id}', name: 'app_discussion_show', methods: ['GET', 'POST'])]
public function show(
    Request $request,
    Discussion $discussion,
    EntityManagerInterface $entityManager,
    CommentaireRepository $commentaireRepository,
    UtilisateurRepository $userRepository
): Response {

    $session = $request->getSession();

    if (!$session->has('user_id')) {
        return $this->redirectToRoute('app_login');
    }

    $user = $userRepository->find($session->get('user_id'));

    if (!$user instanceof Utilisateur) {
        // not logged in
        return $this->redirectToRoute('app_login');
    }
    // Pagination params for comments
    $cpage = max(1, (int) $request->query->get('cpage', 1));
    $cperPage = 10;

    // Build comment query builder for this discussion
    $qb = $commentaireRepository->createQueryBuilder('c')
        ->andWhere('c.discussion = :discussion')
        ->setParameter('discussion', $discussion)
        ->orderBy('c.dateCreation', 'DESC')
        ->setFirstResult(($cpage - 1) * $cperPage)
        ->setMaxResults($cperPage);

    $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($qb);
    $totalComments = count($paginator);
    $cpages = (int) max(1, ceil($totalComments / $cperPage));
    $comments = iterator_to_array($paginator);

    // Create new Comment entity and form for adding comment
    $comment = new \App\Entity\Commentaire();
    $comment->setDiscussion($discussion);
    $comment->setDateCreation(new \DateTime());
    $comment->setOwner($user);

    // Optional: set the current user as owner if your Comment entity has an owner/user field
    $user = $this->getUser();
    if ($user && method_exists($comment, 'setOwner')) {
        $comment->setOwner($user);
    }

    $commentForm = $this->createForm(\App\Form\CommentaireType::class, $comment);

    // Remove discussion field from the form because we're setting it manually
    $commentForm->remove('discussion');

    $commentForm->handleRequest($request);

    if ($commentForm->isSubmitted() && $commentForm->isValid()) {
        $entityManager->persist($comment);
        $entityManager->flush();

        // Redirect to avoid form resubmission on page refresh
        return $this->redirectToRoute('app_discussion_show', ['id' => $discussion->getId()]);
    }

    return $this->render('discussion/show.html.twig', [
        'discussion' => $discussion,
        'comments' => $comments,
        'cpage' => $cpage,
        'cpages' => $cpages,
        'commentForm' => $commentForm->createView(),
    ]);
}


    #[Route('/{id}/edit', name: 'app_discussion_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Discussion $discussion, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DiscussionType::class, $discussion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_discussion_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('discussion/edit.html.twig', [
            'discussion' => $discussion,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_discussion_delete', methods: ['POST'])]

    public function delete(Request $request, Discussion $discussion, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$discussion->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($discussion);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_discussion_index', [], Response::HTTP_SEE_OTHER);
    }
}
