<?php

namespace App\Command;

use App\Notification\DigestMailer;
use App\Repository\UserRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:digest:send', description: 'Email the tasks due today to everyone who opted in')]
final class SendDigestCommand extends Command
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly DigestMailer $digest,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', null, InputOption::VALUE_NONE, 'Send now, even before 7:00 or when already sent today')
            ->setHelp('Run it every hour (e.g. from cron); each user gets one email a day after 7:00 in their household\'s timezone.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sent = 0;
        $now = $this->clock->now();
        $force = (bool) $input->getOption('force');
        foreach ($this->users->findBy(['digestEnabled' => true]) as $user) {
            if (!$force && !$this->digest->isDue($user, $now)) {
                continue;
            }
            if ($this->digest->sendTo($user)) {
                ++$sent;
            }
        }

        $io->success(\sprintf('Sent %d digest %s.', $sent, 1 === $sent ? 'email' : 'emails'));

        return Command::SUCCESS;
    }
}
