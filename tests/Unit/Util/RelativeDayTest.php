<?php

namespace App\Tests\Unit\Util;

use App\Util\RelativeDay;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RelativeDayTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function days(): iterable
    {
        yield 'today' => ['2026-07-12', 'today'];
        yield 'later today still today' => ['2026-07-12 23:00', 'today'];
        yield 'yesterday' => ['2026-07-11', 'yesterday'];
        yield 'tomorrow' => ['2026-07-13', 'tomorrow'];
        yield 'days ago' => ['2026-07-02', '10 days ago'];
        yield 'in days' => ['2026-07-15', 'in 3 days'];
        yield 'across months' => ['2026-06-30', '12 days ago'];
    }

    #[DataProvider('days')]
    public function testFormat(string $date, string $expected): void
    {
        self::assertSame($expected, RelativeDay::format(new DateTimeImmutable($date), new DateTimeImmutable('2026-07-12 08:00')));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function durations(): iterable
    {
        yield 'same day' => ['2026-08-16', 'less than a day'];
        yield 'days' => ['2026-08-13', '3 days'];
        yield 'weeks' => ['2026-07-30', '2 weeks'];
        yield 'one month' => ['2026-07-16', '1 month'];
        yield 'year and months' => ['2025-06-01', '1 year and 2 months'];
        yield 'exact years' => ['2024-08-16', '2 years'];
        yield 'future date' => ['2026-09-01', 'less than a day'];
    }

    #[DataProvider('durations')]
    public function testDuration(string $since, string $expected): void
    {
        self::assertSame($expected, RelativeDay::duration(new DateTimeImmutable($since), new DateTimeImmutable('2026-08-16')));
    }
}
