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
        $csp = (string) $client->getResponse()->headers->get('content-security-policy');
        self::assertStringContainsString("frame-ancestors 'none'", $csp);
        // AssetMapper turns `import './styles/app.css'` into a data: module;
        // blocking it stops all JavaScript (Stimulus) from loading.
        self::assertMatchesRegularExpression('/script-src [^;]*data:/', $csp);
    }

    public function testJsonResponsesHaveNoPageCsp(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        self::assertResponseHeaderSame('x-content-type-options', 'nosniff');
        self::assertFalse($client->getResponse()->headers->has('content-security-policy'));
    }
}
