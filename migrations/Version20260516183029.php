<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260516183029 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add daily digest preference';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE
              app_user
            ADD
              digest_enabled TINYINT DEFAULT 0 NOT NULL,
            ADD
              last_digest_on DATE DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user DROP digest_enabled, DROP last_digest_on');
    }
}
