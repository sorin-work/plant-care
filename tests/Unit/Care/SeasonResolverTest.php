<?php

namespace App\Tests\Unit\Care;

use App\Care\SeasonResolver;
use App\Enum\Hemisphere;
use App\Enum\Season;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SeasonResolverTest extends TestCase
{
    /**
     * @return iterable<string, array{string, Season}>
     */
    public static function northernDates(): iterable
    {
        yield 'new year' => ['2026-01-01', Season::Winter];
        yield 'mid january' => ['2026-01-15', Season::Winter];
        yield 'first of december' => ['2025-12-01', Season::Winter];
        yield 'spring equinox' => ['2026-03-20', Season::Spring];
        yield 'end of may' => ['2026-05-31', Season::Spring];
        yield 'first of june' => ['2026-06-01', Season::Summer];
        yield 'august' => ['2026-08-15', Season::Summer];
        yield 'september' => ['2026-09-01', Season::Autumn];
        yield 'end of november' => ['2026-11-30', Season::Autumn];
    }

    #[DataProvider('northernDates')]
    public function testNorthernHemisphere(string $date, Season $expected): void
    {
        self::assertSame($expected, (new SeasonResolver())->seasonOn(new DateTimeImmutable($date)));
    }

    public function testSouthernHemisphereIsFlipped(): void
    {
        $resolver = new SeasonResolver();

        self::assertSame(Season::Summer, $resolver->seasonOn(new DateTimeImmutable('2026-01-10'), Hemisphere::South));
        self::assertSame(Season::Winter, $resolver->seasonOn(new DateTimeImmutable('2026-07-10'), Hemisphere::South));
        self::assertSame(Season::Autumn, $resolver->seasonOn(new DateTimeImmutable('2026-04-10'), Hemisphere::South));
        self::assertSame(Season::Spring, $resolver->seasonOn(new DateTimeImmutable('2026-10-10'), Hemisphere::South));
    }
}
