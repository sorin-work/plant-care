<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260228164737 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add personal API tokens';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE
              app_user
            ADD
              api_token_hash VARCHAR(64) DEFAULT NULL,
            ADD
              api_token_created_at DATETIME DEFAULT NULL
        SQL);
        $this->addSql('CREATE UNIQUE INDEX UNIQ_88BDF3E974162BCB ON app_user (api_token_hash)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_88BDF3E974162BCB ON app_user');
        $this->addSql('ALTER TABLE app_user DROP api_token_hash, DROP api_token_created_at');
    }
}
