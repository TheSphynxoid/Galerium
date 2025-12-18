<?php

namespace App\MessageHandler;

use App\Message\EnchereEndMessage;
use App\Repository\EnchereRepository;
use App\Enum\EnchereStatut;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;


class EnchereEndHandler
{
    private WorkflowInterface $workflow;

    public function __construct(
        private EntityManagerInterface $em,
        private EnchereRepository $repository,
        private LoggerInterface $logger,
        Registry $registry
    ) {
        $this->workflow = $registry->get(new \App\Entity\Enchere(), 'enchere');
    }
    public function __invoke(EnchereEndMessage $message): void
    {
        $enchere = $this->repository->find($message->enchereId);

        if (!$enchere) {
            $this->logger->warning('EnchereEndHandler: enchere not found', ['id' => $message->enchereId]);
            return;
        }

        $dateFin = $enchere->getDateFin();
        $now = new \DateTimeImmutable();

        if ($dateFin === null || $dateFin > $now) {
            return;
        }

        if (!$this->workflow->can($enchere, 'finish')) {
            $this->logger->info(
                'EnchereEndHandler: transition not allowed',
                ['id' => $enchere->getId()]
            );
            return;
        }

        $this->workflow->apply($enchere, 'finish');

        $this->em->flush();

        $this->logger->info(
            'EnchereEndHandler: enchere finished via workflow',
            ['id' => $enchere->getId()]
        );
    }
}
