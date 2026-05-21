<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260521182346 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create household invitations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE invitation (
              id INT AUTO_INCREMENT NOT NULL,
              role VARCHAR(16) NOT NULL,
              token_hash VARCHAR(64) NOT NULL,
              expires_at DATETIME NOT NULL,
              accepted_at DATETIME DEFAULT NULL,
              created_at DATETIME NOT NULL,
              household_id INT NOT NULL,
              invited_by_id INT DEFAULT NULL,
              UNIQUE INDEX UNIQ_F11D61A2B3BC57DA (token_hash),
              INDEX IDX_F11D61A2E79FF843 (household_id),
              INDEX IDX_F11D61A2A7B4A7E3 (invited_by_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              invitation
            ADD
              CONSTRAINT FK_F11D61A2E79FF843 FOREIGN KEY (household_id) REFERENCES household (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              invitation
            ADD
              CONSTRAINT FK_F11D61A2A7B4A7E3 FOREIGN KEY (invited_by_id) REFERENCES app_user (id) ON DELETE
            SET
              NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invitation DROP FOREIGN KEY FK_F11D61A2E79FF843');
        $this->addSql('ALTER TABLE invitation DROP FOREIGN KEY FK_F11D61A2A7B4A7E3');
        $this->addSql('DROP TABLE invitation');
    }
}
