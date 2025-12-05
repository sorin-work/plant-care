<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251205191839 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create care schedules';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE care_schedule (
              id INT AUTO_INCREMENT NOT NULL,
              type VARCHAR(16) NOT NULL,
              interval_days INT NOT NULL,
              seasonal TINYINT(1) NOT NULL,
              last_done_on DATE DEFAULT NULL,
              next_due_on DATE NOT NULL,
              snoozed_until DATE DEFAULT NULL,
              plant_id INT NOT NULL,
              INDEX IDX_CCBB4461D935652 (plant_id),
              INDEX idx_schedule_next_due (next_due_on),
              UNIQUE INDEX uniq_schedule_plant_type (plant_id, type),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              care_schedule
            ADD
              CONSTRAINT FK_CCBB4461D935652 FOREIGN KEY (plant_id) REFERENCES plant (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE care_schedule DROP FOREIGN KEY FK_CCBB4461D935652');
        $this->addSql('DROP TABLE care_schedule');
    }
}
