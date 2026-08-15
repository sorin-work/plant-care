<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SecurityHeadersTest extends WebTestCase
{
    public function testHtmlPagesSendSecurityHeaders(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        self::assertResponseHeaderSame('x-frame-options', 'DENY');
        self::assertResponseHeaderSame('x-content-type-options', 'nosniff');
        self::assertStringContainsString("frame-ancestors 'none'", (string) $client->getResponse()->headers->get('content-security-policy'));
    }

    public function testJsonResponsesHaveNoPageCsp(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        self::assertResponseHeaderSame('x-content-type-options', 'nosniff');
        self::assertFalse($client->getResponse()->headers->has('content-security-policy'));
    }
}
