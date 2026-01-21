<?php

namespace App\Command;

use App\Demo\DemoSeeder;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:demo:seed', description: 'Create the demo account with sample plants and care history')]
final class SeedDemoCommand extends Command
{
    public function __construct(
        private readonly DemoSeeder $seeder,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->seeder->exists()) {
            $io->note('The demo account already exists, nothing to do.');

            return Command::SUCCESS;
        }

        $this->seeder->seed($this->clock->now());
        $io->success(\sprintf('Demo account created: %s / %s', DemoSeeder::EMAIL, DemoSeeder::PASSWORD));

        return Command::SUCCESS;
    }
}
