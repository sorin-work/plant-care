<?php

namespace App\Command;

use App\Care\DueTaskFinder;
use App\Household\HouseholdClock;
use App\Repository\HouseholdRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:tasks:due', description: 'List the care tasks that are due today, per household')]
final class DueTasksCommand extends Command
{
    public function __construct(
        private readonly HouseholdRepository $households,
        private readonly DueTaskFinder $finder,
        private readonly HouseholdClock $clock,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('household', null, InputOption::VALUE_REQUIRED, 'Only this household id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $id = $input->getOption('household');
        $households = null === $id ? $this->households->findBy([], ['id' => 'ASC']) : array_filter([$this->households->find((int) $id)]);

        if ([] === $households) {
            $io->warning('No households found.');

            return Command::SUCCESS;
        }

        $total = 0;
        foreach ($households as $household) {
            $today = $this->clock->today($household);
            $tasks = $this->finder->dueOn($household, $today);
            $total += \count($tasks);

            $io->section(\sprintf('%s (%s)', $household->getName(), $today->format('D j M')));
            if ([] === $tasks) {
                $io->text('Nothing due.');
                continue;
            }

            $rows = [];
            foreach ($tasks as $task) {
                $rows[] = [
                    $task->schedule->getType()->label(),
                    $task->plant()->getName(),
                    $task->plant()->getRoom()?->getName() ?? '-',
                    $task->isOverdue() ? $task->daysOverdue.' days late' : 'today',
                ];
            }
            $io->table(['Task', 'Plant', 'Room', 'Due'], $rows);
        }

        $io->success(\sprintf('%d %s due.', $total, 1 === $total ? 'task' : 'tasks'));

        return Command::SUCCESS;
    }
}
