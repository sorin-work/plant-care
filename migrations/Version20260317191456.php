<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260317191456 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add calendar feed token';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user ADD calendar_token VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_88BDF3E93363A255 ON app_user (calendar_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_88BDF3E93363A255 ON app_user');
        $this->addSql('ALTER TABLE app_user DROP calendar_token');
    }
}
