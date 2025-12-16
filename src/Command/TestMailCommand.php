<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'test:mail',
    description: 'Send a test email using Mailtrap',
)]
class TestMailCommand extends Command
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        parent::__construct();
        $this->mailer = $mailer;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (new Email())
            ->from('test@forum.com')
            ->to('admin@forum.com')
            ->subject('Mailtrap Test')
            ->text('If you see this, Mailtrap works!');

        $this->mailer->send($email);

        $output->writeln('<info>Email sent successfully!</info>');

        return Command::SUCCESS;
    }
}
