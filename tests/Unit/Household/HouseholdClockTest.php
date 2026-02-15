<?php

namespace App\Tests\Unit\Household;

use App\Entity\Household;
use App\Household\HouseholdClock;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class HouseholdClockTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function moments(): iterable
    {
        yield 'utc household' => ['2026-03-10 20:00:00 UTC', 'UTC', '2026-03-10'];
        yield 'auckland is already tomorrow' => ['2026-03-10 20:00:00 UTC', 'Pacific/Auckland', '2026-03-11'];
        yield 'sydney morning' => ['2026-07-01 22:30:00 UTC', 'Australia/Sydney', '2026-07-02'];
        yield 'new york is still yesterday' => ['2026-03-11 02:00:00 UTC', 'America/New_York', '2026-03-10'];
        yield 'berlin just after midnight' => ['2026-01-04 23:30:00 UTC', 'Europe/Berlin', '2026-01-05'];
    }

    #[DataProvider('moments')]
    public function testTodayUsesHouseholdTimezone(string $now, string $timezone, string $expected): void
    {
        $clock = new HouseholdClock(new MockClock($now));
        $household = (new Household('Home'))->setTimezone($timezone);

        self::assertSame($expected, $clock->today($household)->format('Y-m-d'));
        self::assertSame('00:00:00', $clock->today($household)->format('H:i:s'));
    }
}
