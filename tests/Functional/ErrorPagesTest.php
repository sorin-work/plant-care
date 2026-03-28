<?php

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

/**
 * The error templates are only used with debug off, so they are rendered
 * directly here.
 */
final class ErrorPagesTest extends KernelTestCase
{
    /**
     * @return iterable<string, array{string, int, string}>
     */
    public static function pages(): iterable
    {
        yield 'not found' => ['error404', 404, 'This page has wilted'];
        yield 'forbidden' => ['error403', 403, "You can't do that here"];
        yield 'generic' => ['error', 500, 'Something went wrong'];
    }

    #[DataProvider('pages')]
    public function testErrorTemplateRenders(string $template, int $status, string $heading): void
    {
        $twig = static::getContainer()->get(Environment::class);

        $html = $twig->render('@Twig/Exception/'.$template.'.html.twig', [
            'status_code' => $status,
            'status_text' => 'Error',
            'exception' => null,
        ]);

        self::assertStringContainsString($heading, $html);
        self::assertStringContainsString('href="/"', $html);
    }
}
