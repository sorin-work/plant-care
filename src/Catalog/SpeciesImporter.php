<?php

namespace App\Catalog;

use App\Entity\Species;
use App\Enum\LightLevel;
use App\Repository\SpeciesRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

/**
 * Loads the species catalog from a JSON file, creating or updating entries by
 * scientific name. Existing entries are never deleted.
 */
final class SpeciesImporter
{
    public function __construct(
        private readonly SpeciesRepository $species,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @return array{created: int, updated: int}
     */
    public function importFile(string $path): array
    {
        if (!is_file($path)) {
            throw new InvalidArgumentException(\sprintf('Species file "%s" does not exist.', $path));
        }

        $rows = json_decode((string) file_get_contents($path), true, flags: \JSON_THROW_ON_ERROR);
        if (!\is_array($rows)) {
            throw new InvalidArgumentException('The species file must contain a JSON list.');
        }

        $created = $updated = 0;
        foreach (array_values($rows) as $i => $row) {
            if (!\is_array($row)) {
                throw new InvalidArgumentException(\sprintf('Entry %d is not an object.', $i));
            }
            if ($this->importRow($row, $i)) {
                ++$created;
            } else {
                ++$updated;
            }
        }
        $this->em->flush();

        return ['created' => $created, 'updated' => $updated];
    }

    /**
     * @param array<mixed> $row
     *
     * @return bool true when a new species was created
     */
    private function importRow(array $row, int $index): bool
    {
        foreach (['common', 'scientific', 'water', 'light'] as $key) {
            if (!isset($row[$key])) {
                throw new InvalidArgumentException(\sprintf('Entry %d is missing "%s".', $index, $key));
            }
        }

        $light = LightLevel::tryFrom((string) $row['light'])
            ?? throw new InvalidArgumentException(\sprintf('Entry %d has an unknown light level "%s".', $index, $row['light']));
        $water = (int) $row['water'];
        if ($water < 1) {
            throw new InvalidArgumentException(\sprintf('Entry %d must be watered at least every day.', $index));
        }

        $species = $this->species->findOneByScientificName((string) $row['scientific']);
        $isNew = null === $species;
        if ($isNew) {
            $species = new Species((string) $row['common'], (string) $row['scientific'], $water, $light);
            $this->em->persist($species);
        }

        $species
            ->setCommonName((string) $row['common'])
            ->setWaterIntervalDays($water)
            ->setLight($light)
            ->setFertilizeIntervalDays(isset($row['fertilize']) ? (int) $row['fertilize'] : null)
            ->setRepotIntervalMonths((int) ($row['repot'] ?? 18))
            ->setToxicToPets((bool) ($row['toxic'] ?? false))
            ->setCareNotes(isset($row['notes']) ? (string) $row['notes'] : null);

        return $isNew;
    }
}
