<?php

namespace App\Command;

use App\Notification\DigestMailer;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:digest:send', description: 'Email the tasks due today to everyone who opted in')]
final class SendDigestCommand extends Command
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly DigestMailer $digest,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sent = 0;
        foreach ($this->users->findBy(['digestEnabled' => true]) as $user) {
            if ($this->digest->sendTo($user)) {
                ++$sent;
            }
        }

        $io->success(\sprintf('Sent %d digest %s.', $sent, 1 === $sent ? 'email' : 'emails'));

        return Command::SUCCESS;
    }
}
