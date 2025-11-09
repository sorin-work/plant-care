<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251109113323 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create species catalog';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE species (
              id INT AUTO_INCREMENT NOT NULL,
              common_name VARCHAR(120) NOT NULL,
              scientific_name VARCHAR(120) NOT NULL,
              water_interval_days INT NOT NULL,
              fertilize_interval_days INT DEFAULT NULL,
              repot_interval_months INT NOT NULL,
              light VARCHAR(20) NOT NULL,
              toxic_to_pets TINYINT(1) NOT NULL,
              care_notes LONGTEXT DEFAULT NULL,
              UNIQUE INDEX uniq_species_scientific_name (scientific_name),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE species');
    }
}
