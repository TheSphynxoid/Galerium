<?php

namespace App\EventSubscriber;

use App\Entity\Enchere;
use App\Message\EnchereEndMessage;

use Doctrine\Persistence\Event\LifecycleEventArgs as NewLifecycleEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Psr\Log\LoggerInterface;

class EnchereSubscriber
{
    public function __construct(
        private MessageBusInterface $bus,
        private LoggerInterface $logger
    ){
    }


    public function postPersist(NewLifecycleEventArgs $args): void
    {
        $this->maybeScheduleEnd($args);
    }

    public function postUpdate(NewLifecycleEventArgs $args): void
    {
        $this->maybeScheduleEnd($args);
    }

    private function maybeScheduleEnd(NewLifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Enchere) {
            return;
        }

        $dateFin = $entity->getDateFin();
        if ($dateFin === null) {
            return;
        }

        $now = new \DateTime();
        $delayMs = max(0, (int) (($dateFin->getTimestamp() - $now->getTimestamp()) * 1000));

        $message = new EnchereEndMessage($entity->getId());

        if ($delayMs <= 0) {
            $this->bus->dispatch($message);
            $this->logger->info('EnchereSubscriber: dispatched immediate end message', ['id' => $entity->getId()]);
            return;
        }

        // Use DelayStamp to schedule; depends on transport support
        $envelope = new Envelope($message, [new DelayStamp($delayMs)]);
        $this->bus->dispatch($envelope);
        $this->logger->info('EnchereSubscriber: scheduled end message', ['id' => $entity->getId(), 'delayMs' => $delayMs]);
    }
}
