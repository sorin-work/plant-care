<?php

namespace App\Util;

/**
 * Short initials for avatars, e.g. "Monty the monstera" -> "MT".
 */
final class Initials
{
    public static function of(string $name, int $max = 2): string
    {
        $words = preg_split('/[\s\-_]+/', trim($name), -1, \PREG_SPLIT_NO_EMPTY) ?: [];

        $initials = '';
        foreach (\array_slice($words, 0, $max) as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }

        return '' === $initials ? '?' : $initials;
    }
}
