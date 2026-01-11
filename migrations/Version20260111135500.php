<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260111135500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create journal entries';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE journal_entry (
              id INT AUTO_INCREMENT NOT NULL,
              written_on DATE NOT NULL,
              note LONGTEXT NOT NULL,
              photo VARCHAR(64) DEFAULT NULL,
              created_at DATETIME NOT NULL,
              plant_id INT NOT NULL,
              author_id INT DEFAULT NULL,
              INDEX IDX_C8FAAE5A1D935652 (plant_id),
              INDEX IDX_C8FAAE5AF675F31B (author_id),
              INDEX idx_journal_plant_date (plant_id, written_on),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              journal_entry
            ADD
              CONSTRAINT FK_C8FAAE5A1D935652 FOREIGN KEY (plant_id) REFERENCES plant (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              journal_entry
            ADD
              CONSTRAINT FK_C8FAAE5AF675F31B FOREIGN KEY (author_id) REFERENCES app_user (id) ON DELETE
            SET
              NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE journal_entry DROP FOREIGN KEY FK_C8FAAE5A1D935652');
        $this->addSql('ALTER TABLE journal_entry DROP FOREIGN KEY FK_C8FAAE5AF675F31B');
        $this->addSql('DROP TABLE journal_entry');
    }
}
