<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;

class EnchereWorkflowSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onWorkflowEnchereCompletedFinish(CompletedEvent $event): void
    {
        $enchere = $event->getSubject();

        $this->logger->info(
            sprintf(
                'Enchere terminée (ID: %d)',
                $enchere->getId()
            )
        );
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.enchere.completed.finish' => 'onWorkflowEnchereCompletedFinish',
        ];
    }
}
