<?php

namespace App\Tests\Unit\Care;

use App\Care\ScheduleFactory;
use App\Entity\CareSchedule;
use App\Entity\Household;
use App\Entity\Plant;
use App\Entity\Species;
use App\Enum\CareTaskType;
use App\Enum\LightLevel;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ScheduleFactoryTest extends TestCase
{
    /**
     * @param list<CareSchedule> $schedules
     *
     * @return array<string, CareSchedule>
     */
    private function byType(array $schedules): array
    {
        $out = [];
        foreach ($schedules as $s) {
            $out[$s->getType()->value] = $s;
        }

        return $out;
    }

    public function testUsesSpeciesDefaults(): void
    {
        $species = (new Species('Monstera', 'Monstera deliciosa', 7, LightLevel::BrightIndirect))
            ->setFertilizeIntervalDays(30)
            ->setRepotIntervalMonths(24);
        $plant = (new Plant(new Household('Home')))->setSpecies($species);

        $schedules = $this->byType((new ScheduleFactory())->createDefaults($plant, new DateTimeImmutable('2026-03-01 15:00')));

        self::assertSame(['water', 'fertilize', 'repot'], array_keys($schedules));
        self::assertSame(7, $schedules['water']->getIntervalDays());
        self::assertSame('2026-03-01', $schedules['water']->getNextDueOn()->format('Y-m-d'));
        self::assertSame('2026-03-31', $schedules['fertilize']->getNextDueOn()->format('Y-m-d'));
        self::assertSame(720, $schedules['repot']->getIntervalDays());
        self::assertFalse($schedules['repot']->isSeasonal());
        self::assertCount(3, $plant->getSchedules());
    }

    public function testWithoutSpeciesOnlyWaterAndRepot(): void
    {
        $plant = new Plant(new Household('Home'));

        $schedules = $this->byType((new ScheduleFactory())->createDefaults($plant, new DateTimeImmutable('2026-03-01')));

        self::assertSame(['water', 'repot'], array_keys($schedules));
        self::assertSame(ScheduleFactory::DEFAULT_WATER_DAYS, $schedules['water']->getIntervalDays());
    }

    public function testSpeciesThatIsNeverFertilized(): void
    {
        $species = new Species('Venus flytrap', 'Dionaea muscipula', 3, LightLevel::Direct);
        $plant = (new Plant(new Household('Home')))->setSpecies($species);

        $types = array_map(fn (CareSchedule $s) => $s->getType(), (new ScheduleFactory())->createDefaults($plant, new DateTimeImmutable()));

        self::assertNotContains(CareTaskType::Fertilize, $types);
    }

    public function testRepotIsCountedFromAcquisitionDate(): void
    {
        $plant = (new Plant(new Household('Home')))->setAcquiredOn(new DateTimeImmutable('2025-12-01'));

        $schedules = $this->byType((new ScheduleFactory())->createDefaults($plant, new DateTimeImmutable('2026-03-01')));

        self::assertSame('2027-05-25', $schedules['repot']->getNextDueOn()->format('Y-m-d'));
    }

    public function testOverdueRepotIsDueToday(): void
    {
        $plant = (new Plant(new Household('Home')))->setAcquiredOn(new DateTimeImmutable('2020-01-01'));

        $schedules = $this->byType((new ScheduleFactory())->createDefaults($plant, new DateTimeImmutable('2026-03-01')));

        self::assertSame('2026-03-01', $schedules['repot']->getNextDueOn()->format('Y-m-d'));
    }
}
