<?php

namespace App\Tests\Unit\Pagination;

use App\Pagination\Page;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PageTest extends TestCase
{
    /**
     * @return iterable<string, array{int, int, int}>
     */
    public static function counts(): iterable
    {
        yield 'no results still has one page' => [0, 10, 1];
        yield 'fewer than a page' => [7, 10, 1];
        yield 'exactly one page' => [10, 10, 1];
        yield 'exactly two pages' => [20, 10, 2];
        yield 'one more than a page' => [11, 10, 2];
        yield 'partial last page' => [25, 10, 3];
        yield 'single item' => [1, 25, 1];
    }

    #[DataProvider('counts')]
    public function testCountPages(int $total, int $perPage, int $expected): void
    {
        self::assertSame($expected, Page::countPages($total, $perPage));
    }

    public function testClamp(): void
    {
        self::assertSame(1, Page::clamp(0, 3));
        self::assertSame(1, Page::clamp(-4, 3));
        self::assertSame(2, Page::clamp(2, 3));
        self::assertSame(3, Page::clamp(99, 3));
    }
}
