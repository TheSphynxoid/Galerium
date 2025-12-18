<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function notify(User $user, string $type, array $payload = []): void
    {
        $notification = new Notification();
        $notification->setUser($user)
            ->setType($type)
            ->setPayload($payload);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }
}

