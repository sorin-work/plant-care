<?php

namespace App\Tests\Unit\Twig;

use App\Twig\TextExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TextExtensionTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function texts(): iterable
    {
        yield 'plain text' => ['Watered today', 'Watered today'];
        yield 'single link' => [
            'Guide: https://example.com/care',
            'Guide: <a href="https://example.com/care" rel="nofollow noopener" target="_blank">https://example.com/care</a>',
        ];
        yield 'trailing punctuation stays outside' => [
            'See https://example.com/a.',
            'See <a href="https://example.com/a" rel="nofollow noopener" target="_blank">https://example.com/a</a>.',
        ];
        yield 'query string is escaped' => [
            'https://shop.example/p?id=1&ref=2',
            '<a href="https://shop.example/p?id=1&amp;ref=2" rel="nofollow noopener" target="_blank">https://shop.example/p?id=1&amp;ref=2</a>',
        ];
        yield 'not a link' => ['ftp://old.example', 'ftp://old.example'];
    }

    #[DataProvider('texts')]
    public function testAutolink(string $input, string $expected): void
    {
        self::assertSame($expected, (new TextExtension())->autolink($input));
    }
}
