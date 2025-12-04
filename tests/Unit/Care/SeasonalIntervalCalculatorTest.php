<?php

namespace App\Tests\Unit\Care;

use App\Care\SeasonalIntervalCalculator;
use App\Enum\CareTaskType;
use App\Enum\Season;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SeasonalIntervalCalculatorTest extends TestCase
{
    /**
     * @return iterable<string, array{CareTaskType, int, Season, int}>
     */
    public static function intervals(): iterable
    {
        yield 'water in spring keeps base' => [CareTaskType::Water, 7, Season::Spring, 7];
        yield 'water in winter is stretched' => [CareTaskType::Water, 10, Season::Winter, 15];
        yield 'water in summer is shortened' => [CareTaskType::Water, 8, Season::Summer, 6];
        yield 'water in autumn' => [CareTaskType::Water, 10, Season::Autumn, 12];
        yield 'fertilize in winter is doubled' => [CareTaskType::Fertilize, 30, Season::Winter, 60];
        yield 'fertilize in summer keeps base' => [CareTaskType::Fertilize, 14, Season::Summer, 14];
        yield 'repot ignores seasons' => [CareTaskType::Repot, 540, Season::Winter, 540];
    }

    #[DataProvider('intervals')]
    public function testEffectiveInterval(CareTaskType $type, int $base, Season $season, int $expected): void
    {
        self::assertSame($expected, (new SeasonalIntervalCalculator())->effectiveInterval($type, $base, $season));
    }

    public function testRejectsZeroBaseInterval(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SeasonalIntervalCalculator())->effectiveInterval(CareTaskType::Water, 0, Season::Spring);
    }
}
