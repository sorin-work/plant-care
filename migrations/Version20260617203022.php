<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260617203022 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow households to add their own species';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_species_scientific_name ON species');
        $this->addSql('ALTER TABLE species ADD household_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              species
            ADD
              CONSTRAINT FK_A50FF712E79FF843 FOREIGN KEY (household_id) REFERENCES household (id) ON DELETE CASCADE
        SQL);
        $this->addSql('CREATE INDEX IDX_A50FF712E79FF843 ON species (household_id)');
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_species_household_scientific_name ON species (household_id, scientific_name)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE species DROP FOREIGN KEY FK_A50FF712E79FF843');
        $this->addSql('DROP INDEX IDX_A50FF712E79FF843 ON species');
        $this->addSql('DROP INDEX uniq_species_household_scientific_name ON species');
        $this->addSql('ALTER TABLE species DROP household_id');
        $this->addSql('CREATE UNIQUE INDEX uniq_species_scientific_name ON species (scientific_name)');
    }
}
