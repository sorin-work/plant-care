<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251210212754 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create care events';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE care_event (
              id INT AUTO_INCREMENT NOT NULL,
              type VARCHAR(16) NOT NULL,
              action VARCHAR(16) NOT NULL,
              happened_on DATE NOT NULL,
              created_at DATETIME NOT NULL,
              plant_id INT NOT NULL,
              user_id INT DEFAULT NULL,
              INDEX IDX_A5EDF0D51D935652 (plant_id),
              INDEX IDX_A5EDF0D5A76ED395 (user_id),
              INDEX idx_care_event_plant_date (plant_id, happened_on),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              care_event
            ADD
              CONSTRAINT FK_A5EDF0D51D935652 FOREIGN KEY (plant_id) REFERENCES plant (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              care_event
            ADD
              CONSTRAINT FK_A5EDF0D5A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE
            SET
              NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE care_event DROP FOREIGN KEY FK_A5EDF0D51D935652');
        $this->addSql('ALTER TABLE care_event DROP FOREIGN KEY FK_A5EDF0D5A76ED395');
        $this->addSql('DROP TABLE care_event');
    }
}
