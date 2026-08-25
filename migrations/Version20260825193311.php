<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260825193311 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remember why a plant was archived';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE plant ADD archive_reason VARCHAR(16) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE plant DROP archive_reason');
    }
}
