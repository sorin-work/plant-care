<?php

namespace App\Twig;

use App\Util\Initials;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class TextExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('autolink', $this->autolink(...), ['is_safe' => ['html']]),
            new TwigFilter('initials', Initials::of(...)),
        ];
    }

    /**
     * Turns http(s) URLs in plain text into links, e.g. a link to the shop a
     * plant came from or a care guide.
     */
    public function autolink(string $text): string
    {
        // The output is marked safe, so everything that is not one of our
        // links must be escaped here.
        $parts = preg_split('~(\bhttps?://[^\s<>"\']+)~i', $text, -1, \PREG_SPLIT_DELIM_CAPTURE) ?: [$text];

        $html = '';
        foreach ($parts as $i => $part) {
            if (0 === $i % 2) {
                $html .= self::escape($part);
                continue;
            }

            $url = rtrim($part, '.,;:!?)');
            $safe = self::escape($url);
            $html .= \sprintf('<a href="%s" rel="nofollow noopener" target="_blank">%s</a>%s', $safe, $safe, self::escape(substr($part, \strlen($url))));
        }

        return $html;
    }

    private static function escape(string $text): string
    {
        return htmlspecialchars($text, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
    }
}
