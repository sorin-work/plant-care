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
use PHPUnit\Framework\TestCase;

final class CareSchedulerTest extends TestCase
{
    private CareScheduler $scheduler;

    protected function setUp(): void
    {
        $this->scheduler = new CareScheduler(new SeasonResolver(), new SeasonalIntervalCalculator());
    }

    private function schedule(CareTaskType $type = CareTaskType::Water, int $days = 10, Hemisphere $hemisphere = Hemisphere::North): CareSchedule
    {
        $plant = new Plant((new Household('Home'))->setHemisphere($hemisphere));

        return new CareSchedule($plant, $type, $days, new DateTimeImmutable('2026-04-01'));
    }

    public function testCompletingInSpringUsesBaseInterval(): void
    {
        $schedule = $this->schedule();
        $this->scheduler->complete($schedule, new DateTimeImmutable('2026-04-10 18:30'));

        self::assertSame('2026-04-10', $schedule->getLastDoneOn()?->format('Y-m-d'));
        self::assertSame('2026-04-20', $schedule->getNextDueOn()->format('Y-m-d'));
    }

    public function testCompletingInWinterStretchesInterval(): void
    {
        $schedule = $this->schedule();
        $this->scheduler->complete($schedule, new DateTimeImmutable('2026-01-05'));

        self::assertSame('2026-01-20', $schedule->getNextDueOn()->format('Y-m-d'));
    }

    public function testSouthernWinterIsInJuly(): void
    {
        $schedule = $this->schedule(hemisphere: Hemisphere::South);
        $this->scheduler->complete($schedule, new DateTimeImmutable('2026-07-05'));

        self::assertSame('2026-07-20', $schedule->getNextDueOn()->format('Y-m-d'));
    }

    public function testNonSeasonalScheduleKeepsBaseInterval(): void
    {
        $schedule = $this->schedule()->setSeasonal(false);
        $this->scheduler->complete($schedule, new DateTimeImmutable('2026-01-05'));

        self::assertSame('2026-01-15', $schedule->getNextDueOn()->format('Y-m-d'));
    }

    public function testCompletingClearsSnooze(): void
    {
        $schedule = $this->schedule()->setSnoozedUntil(new DateTimeImmutable('2026-04-12'));
        $this->scheduler->complete($schedule, new DateTimeImmutable('2026-04-10'));

        self::assertNull($schedule->getSnoozedUntil());
    }

    public function testNextDueAcrossMonthBoundary(): void
    {
        $schedule = $this->schedule(CareTaskType::Fertilize, 30);

        self::assertSame('2026-06-27', $this->scheduler->nextDueAfter($schedule, new DateTimeImmutable('2026-05-28'))->format('Y-m-d'));
    }
}
