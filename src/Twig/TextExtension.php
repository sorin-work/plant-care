<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class TextExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('autolink', $this->autolink(...), ['is_safe' => ['html']]),
        ];
    }

    /**
     * Turns http(s) URLs in plain text into links, e.g. a link to the shop a
     * plant came from or a care guide.
     */
    public function autolink(string $text): string
    {
        return (string) preg_replace_callback(
            '~\bhttps?://[^\s<>"\']+~i',
            static function (array $m): string {
                $url = rtrim($m[0], '.,;:!?)');
                $rest = substr($m[0], \strlen($url));
                $safe = htmlspecialchars($url, \ENT_QUOTES);

                return \sprintf('<a href="%s" rel="nofollow noopener" target="_blank">%s</a>%s', $safe, $safe, $rest);
            },
            $text,
        );
    }
}
