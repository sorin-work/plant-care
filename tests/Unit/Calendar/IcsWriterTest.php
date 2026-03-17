<?php

namespace App\Tests\Unit\Calendar;

use App\Calendar\IcsWriter;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class IcsWriterTest extends TestCase
{
    public function testRendersAllDayEvent(): void
    {
        $ics = new IcsWriter('Plants');
        $ics->addAllDayEvent('water-1@plant-care', new DateTimeImmutable('2026-03-20'), 'Water Monty', 'Living room', new DateTimeImmutable('2026-03-17 10:00:00 UTC'));
        $out = $ics->render();

        self::assertStringStartsWith("BEGIN:VCALENDAR\r\n", $out);
        self::assertStringEndsWith("END:VCALENDAR\r\n", $out);
        self::assertStringContainsString("DTSTART;VALUE=DATE:20260320\r\n", $out);
        self::assertStringContainsString("DTEND;VALUE=DATE:20260321\r\n", $out);
        self::assertStringContainsString("DTSTAMP:20260317T100000Z\r\n", $out);
        self::assertStringContainsString("SUMMARY:Water Monty\r\n", $out);
        self::assertStringContainsString("DESCRIPTION:Living room\r\n", $out);
    }

    public function testEscapesSpecialCharacters(): void
    {
        self::assertSame('a\, b\; c\\\\ d\nnext', IcsWriter::escape("a, b; c\\ d\nnext"));
    }

    public function testFoldsLongLines(): void
    {
        $line = 'SUMMARY:'.str_repeat('x', 100);
        $folded = IcsWriter::fold($line);

        foreach (explode("\r\n", $folded) as $part) {
            self::assertLessThanOrEqual(75, \strlen($part));
        }
        self::assertSame($line, str_replace("\r\n ", '', $folded));
    }

    public function testFoldingKeepsMultibyteCharactersIntact(): void
    {
        $line = 'SUMMARY:'.str_repeat('ö', 60);
        $folded = IcsWriter::fold($line);

        self::assertTrue(mb_check_encoding($folded, 'UTF-8'));
        foreach (explode("\r\n ", $folded) as $part) {
            self::assertTrue(mb_check_encoding($part, 'UTF-8'));
        }
    }
}
