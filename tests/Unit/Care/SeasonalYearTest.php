<?php

namespace App\Tests\Unit\Care;

use App\Care\CareScheduler;
use App\Care\SeasonalIntervalCalculator;
use App\Care\SeasonResolver;
use App\Entity\CareSchedule;
use App\Entity\Household;
use App\Entity\Plant;
use App\Enum\CareTaskType;
use App\Enum\Hemisphere;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Waters a plant on time for a whole year and checks that it is watered
 * less often in winter than in summer.
 */
final class SeasonalYearTest extends TestCase
{
    /**
     * @return iterable<string, array{Hemisphere, int, int}>
     */
    public static function hemispheres(): iterable
    {
        // [hemisphere, winter month, summer month]
        yield 'north' => [Hemisphere::North, 1, 7];
        yield 'south' => [Hemisphere::South, 7, 1];
    }

    #[DataProvider('hemispheres')]
    public function testWateringFollowsTheSeasons(Hemisphere $hemisphere, int $winterMonth, int $summerMonth): void
    {
        $scheduler = new CareScheduler(new SeasonResolver(), new SeasonalIntervalCalculator());
        $plant = new Plant((new Household('Home'))->setHemisphere($hemisphere));
        $schedule = new CareSchedule($plant, CareTaskType::Water, 8, new DateTimeImmutable('2026-01-01'));

        $perMonth = array_fill(1, 12, 0);
        while ($schedule->getNextDueOn() < new DateTimeImmutable('2027-01-01')) {
            $day = $schedule->getNextDueOn();
            ++$perMonth[(int) $day->format('n')];
            $scheduler->complete($schedule, $day);
        }

        self::assertGreaterThan($perMonth[$winterMonth], $perMonth[$summerMonth]);
        self::assertGreaterThanOrEqual(40, array_sum($perMonth));
        self::assertLessThanOrEqual(46, array_sum($perMonth));
    }

    public function testNonSeasonalScheduleIsEvenAllYear(): void
    {
        $scheduler = new CareScheduler(new SeasonResolver(), new SeasonalIntervalCalculator());
        $schedule = (new CareSchedule(new Plant(new Household('Home')), CareTaskType::Water, 7, new DateTimeImmutable('2026-01-01')))->setSeasonal(false);

        $count = 0;
        while ($schedule->getNextDueOn() < new DateTimeImmutable('2027-01-01')) {
            $scheduler->complete($schedule, $schedule->getNextDueOn());
            ++$count;
        }

        self::assertSame(53, $count);
    }
}
