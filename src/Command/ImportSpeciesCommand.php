<?php

namespace App\Command;

use App\Catalog\SpeciesImporter;
use InvalidArgumentException;
use JsonException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'app:species:import', description: 'Create or update the species catalog from a JSON file')]
final class ImportSpeciesCommand extends Command
{
    public function __construct(
        private readonly SpeciesImporter $importer,
        #[Autowire('%kernel.project_dir%/data/species.json')]
        private readonly string $defaultFile,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::OPTIONAL, 'Path to the JSON file', $this->defaultFile);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $result = $this->importer->importFile((string) $input->getArgument('file'));
        } catch (InvalidArgumentException|JsonException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success(\sprintf('Species catalog imported: %d created, %d updated.', $result['created'], $result['updated']));

        return Command::SUCCESS;
    }
}
