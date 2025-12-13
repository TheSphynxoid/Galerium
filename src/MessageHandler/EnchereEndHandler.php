<?php

namespace App\MessageHandler;

use App\Message\EnchereEndMessage;
use App\Repository\EnchereRepository;
use App\Enum\EnchereStatut;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class EnchereEndHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private EnchereRepository $repository,
        private LoggerInterface $logger
    ){
    }

    public function __invoke(EnchereEndMessage $message)
    {
        $enchere = $this->repository->find($message->enchereId);
        if (!$enchere) {
            $this->logger->warning('EnchereEndHandler: enchere not found', ['id' => $message->enchereId]);
            return;
        }

        $dateFin = $enchere->getDateFin();
        $now = new \DateTime();

        if ($dateFin === null || $dateFin > $now) {
            // Not yet finished or no datefin; skip
            $this->logger->info('EnchereEndHandler: not due yet or no dateFin', ['id' => $message->enchereId]);
            return;
        }

        $current = $enchere->getStatut();
        if ($current && $current->value === EnchereStatut::TERMINEE->value) {
            $this->logger->info('EnchereEndHandler: already terminated', ['id' => $message->enchereId]);
            return;
        }

        $enchere->setStatut(EnchereStatut::TERMINEE);
        $this->em->persist($enchere);
        $this->em->flush();

        $this->logger->info('EnchereEndHandler: status set to TERMINEE', ['id' => $message->enchereId]);
    }
}
