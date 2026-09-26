<?php

namespace App\Tests\Unit\Catalog;

use App\Enum\LightLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Sanity checks for the bundled data/species.json.
 */
final class SpeciesDataTest extends TestCase
{
    /**
     * @return list<array<string, mixed>>
     */
    private static function rows(): array
    {
        $rows = json_decode((string) file_get_contents(__DIR__.'/../../../data/species.json'), true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($rows);

        return $rows;
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function species(): iterable
    {
        foreach (self::rows() as $row) {
            yield (string) $row['scientific'] => [$row];
        }
    }

    #[DataProvider('species')]
    public function testEntryIsComplete(array $row): void
    {
        self::assertNotSame('', trim((string) $row['common']));
        self::assertNotNull(LightLevel::tryFrom((string) $row['light']), 'unknown light level');
        self::assertGreaterThanOrEqual(1, $row['water']);
        self::assertLessThanOrEqual(90, $row['water']);
        self::assertTrue(null === $row['fertilize'] || $row['fertilize'] >= 7);
        self::assertGreaterThanOrEqual(6, $row['repot']);
        self::assertIsBool($row['toxic']);
    }

    public function testScientificNamesAreUnique(): void
    {
        $names = array_column(self::rows(), 'scientific');

        self::assertSame(\count($names), \count(array_unique($names)));
    }
}
