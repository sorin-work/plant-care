<?php

namespace App\Tests\Unit\Care;

use App\Care\PlantSorter;
use App\Entity\CareSchedule;
use App\Entity\Household;
use App\Entity\Plant;
use App\Enum\CareTaskType;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class PlantSorterTest extends TestCase
{
    /**
     * @return list<Plant>
     */
    private function plants(): array
    {
        $household = new Household('Home');
        $make = function (string $name, ?string $due) use ($household): Plant {
            $plant = (new Plant($household))->setName($name);
            if (null !== $due) {
                new CareSchedule($plant, CareTaskType::Water, 7, new DateTimeImmutable($due));
            }

            return $plant;
        };

        return [$make('fern 10', '2026-03-25'), $make('Fern 2', null), $make('aloe', '2026-03-21')];
    }

    /**
     * @param list<Plant> $plants
     *
     * @return list<string>
     */
    private function names(array $plants): array
    {
        return array_map(fn (Plant $p) => $p->getName(), $plants);
    }

    public function testSortByNameIsNaturalAndCaseInsensitive(): void
    {
        self::assertSame(['aloe', 'Fern 2', 'fern 10'], $this->names((new PlantSorter())->sort($this->plants(), PlantSorter::NAME)));
    }

    public function testSortByNextDuePutsPlantsWithoutSchedulesLast(): void
    {
        self::assertSame(['aloe', 'fern 10', 'Fern 2'], $this->names((new PlantSorter())->sort($this->plants(), PlantSorter::NEXT_DUE)));
    }

    public function testUnknownSortFallsBackToName(): void
    {
        self::assertSame(['aloe', 'Fern 2', 'fern 10'], $this->names((new PlantSorter())->sort($this->plants(), 'bogus')));
    }

    public function testSortByNextDueRespectsSnoozes(): void
    {
        $household = new Household('Home');
        $snoozed = (new Plant($household))->setName('Snoozed');
        (new CareSchedule($snoozed, CareTaskType::Water, 7, new DateTimeImmutable('2026-09-01')))->setSnoozedUntil(new DateTimeImmutable('2026-09-10'));
        $soon = (new Plant($household))->setName('Soon');
        new CareSchedule($soon, CareTaskType::Water, 7, new DateTimeImmutable('2026-09-05'));

        self::assertSame(['Soon', 'Snoozed'], $this->names((new PlantSorter())->sort([$snoozed, $soon], PlantSorter::NEXT_DUE)));
    }
}
