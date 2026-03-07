<?php

namespace App\Tests\Unit\Util;

use App\Util\Initials;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class InitialsTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function names(): iterable
    {
        yield 'single word' => ['Monty', 'M'];
        yield 'two words' => ['golden pothos', 'GP'];
        yield 'only first two words' => ['Monty the monstera', 'MT'];
        yield 'dashes' => ['bird-of-paradise', 'BO'];
        yield 'extra spaces' => ['  fig   tree ', 'FT'];
        yield 'empty' => ['', '?'];
    }

    #[DataProvider('names')]
    public function testInitials(string $name, string $expected): void
    {
        self::assertSame($expected, Initials::of($name));
    }
}
