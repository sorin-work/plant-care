<?php

namespace App\Tests\Functional\Command;

use App\Enum\LightLevel;
use App\Repository\SpeciesRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ImportSpeciesCommandTest extends KernelTestCase
{
    private function tester(): CommandTester
    {
        $application = new Application(self::bootKernel());

        return new CommandTester($application->find('app:species:import'));
    }

    public function testImportsBundledCatalog(): void
    {
        $tester = $this->tester();
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('42 created, 0 updated', $tester->getDisplay());

        $monstera = static::getContainer()->get(SpeciesRepository::class)->findOneByScientificName('Monstera deliciosa');
        self::assertNotNull($monstera);
        self::assertSame(7, $monstera->getWaterIntervalDays());
        self::assertSame(LightLevel::BrightIndirect, $monstera->getLight());
        self::assertTrue($monstera->isToxicToPets());
    }

    public function testImportIsIdempotent(): void
    {
        $this->tester()->execute([]);
        $tester = $this->tester();
        $tester->execute([]);

        self::assertStringContainsString('0 created, 42 updated', $tester->getDisplay());
    }

    public function testInvalidLightLevelFails(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'species');
        file_put_contents($file, '[{"common": "Fake", "scientific": "Fakus", "water": 3, "light": "moonlight"}]');

        $tester = $this->tester();
        $tester->execute(['file' => $file]);
        unlink($file);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('unknown light level', $tester->getDisplay());
    }

    public function testMissingFileFails(): void
    {
        $tester = $this->tester();
        $tester->execute(['file' => '/nope/species.json']);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('does not exist', $tester->getDisplay());
    }
}
