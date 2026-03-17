<?php

namespace App\Calendar;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Minimal iCalendar (RFC 5545) writer for all-day events.
 */
final class IcsWriter
{
    /** @var list<string> */
    private array $lines = [];

    public function __construct(string $name)
    {
        $this->lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//plant-care//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.self::escape($name),
        ];
    }

    public function addAllDayEvent(string $uid, DateTimeImmutable $day, string $summary, ?string $description = null, ?DateTimeImmutable $stamp = null): void
    {
        $stamp ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $this->lines[] = 'BEGIN:VEVENT';
        $this->lines[] = 'UID:'.self::escape($uid);
        $this->lines[] = 'DTSTAMP:'.$stamp->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
        $this->lines[] = 'DTSTART;VALUE=DATE:'.$day->format('Ymd');
        $this->lines[] = 'DTEND;VALUE=DATE:'.$day->modify('+1 day')->format('Ymd');
        $this->lines[] = 'SUMMARY:'.self::escape($summary);
        if (null !== $description && '' !== $description) {
            $this->lines[] = 'DESCRIPTION:'.self::escape($description);
        }
        $this->lines[] = 'TRANSP:TRANSPARENT';
        $this->lines[] = 'END:VEVENT';
    }

    public function render(): string
    {
        $out = '';
        foreach ([...$this->lines, 'END:VCALENDAR'] as $line) {
            $out .= self::fold($line)."\r\n";
        }

        return $out;
    }

    public static function escape(string $text): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $text);
    }

    /**
     * Lines longer than 75 octets are folded with CRLF + space, without
     * splitting multi-byte characters.
     */
    public static function fold(string $line): string
    {
        $out = '';
        $current = '';
        foreach (mb_str_split($line) as $char) {
            if (\strlen($current) + \strlen($char) > 75) {
                $out .= $current."\r\n ";
                $current = '';
            }
            $current .= $char;
        }

        return $out.$current;
    }
}
