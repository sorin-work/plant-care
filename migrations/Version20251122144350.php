<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251122144350 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create plants';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE plant (
              id INT AUTO_INCREMENT NOT NULL,
              name VARCHAR(80) NOT NULL,
              light VARCHAR(20) NOT NULL,
              acquired_on DATE DEFAULT NULL,
              notes LONGTEXT DEFAULT NULL,
              archived_at DATETIME DEFAULT NULL,
              created_at DATETIME NOT NULL,
              household_id INT NOT NULL,
              room_id INT DEFAULT NULL,
              species_id INT DEFAULT NULL,
              INDEX IDX_AB030D72E79FF843 (household_id),
              INDEX IDX_AB030D7254177093 (room_id),
              INDEX IDX_AB030D72B2A1D860 (species_id),
              INDEX idx_plant_household_archived (household_id, archived_at),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              plant
            ADD
              CONSTRAINT FK_AB030D72E79FF843 FOREIGN KEY (household_id) REFERENCES household (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              plant
            ADD
              CONSTRAINT FK_AB030D7254177093 FOREIGN KEY (room_id) REFERENCES room (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              plant
            ADD
              CONSTRAINT FK_AB030D72B2A1D860 FOREIGN KEY (species_id) REFERENCES species (id) ON DELETE
            SET
              NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE plant DROP FOREIGN KEY FK_AB030D72E79FF843');
        $this->addSql('ALTER TABLE plant DROP FOREIGN KEY FK_AB030D7254177093');
        $this->addSql('ALTER TABLE plant DROP FOREIGN KEY FK_AB030D72B2A1D860');
        $this->addSql('DROP TABLE plant');
    }
}
