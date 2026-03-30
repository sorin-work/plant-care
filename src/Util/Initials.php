<?php

namespace App\Util;

/**
 * Short initials for avatars, e.g. "Monty the monstera" -> "MT".
 */
final class Initials
{
    public static function of(string $name, int $max = 2): string
    {
        $words = preg_split('/[\s\-_]+/u', trim($name), -1, \PREG_SPLIT_NO_EMPTY) ?: [];

        $initials = '';
        foreach (\array_slice($words, 0, $max) as $word) {
            // first user-perceived character, so "Ö", "é" or an emoji stay intact
            $first = grapheme_substr($word, 0, 1);
            $initials .= mb_strtoupper(false === $first ? mb_substr($word, 0, 1) : $first);
        }

        return '' === $initials ? '?' : $initials;
    }
}
