<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HealthControllerTest extends WebTestCase
{
    public function testHealthReportsDatabaseUp(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertJsonStringEqualsJsonString('{"status":"ok","database":"up","version":"0.9.0"}', (string) $client->getResponse()->getContent());
    }

    public function testHealthOnlyAcceptsGet(): void
    {
        $client = static::createClient();
        $client->request('POST', '/health');

        self::assertResponseStatusCodeSame(405);
    }
}
