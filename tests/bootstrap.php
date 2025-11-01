<?php

use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

// Build a fresh SQLite schema once per run; DAMA wraps every test in a transaction.
$kernel = new Kernel('test', false);
$kernel->boot();
/** @var EntityManagerInterface $em */
$em = $kernel->getContainer()->get('doctrine')->getManager();
$schemaTool = new SchemaTool($em);
$metadata = $em->getMetadataFactory()->getAllMetadata();
$schemaTool->dropDatabase();
$schemaTool->createSchema($metadata);
$kernel->shutdown();
