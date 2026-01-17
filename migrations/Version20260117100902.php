<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260117100902 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add plant cover photo';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE plant ADD cover_photo VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE plant DROP cover_photo');
    }
}
