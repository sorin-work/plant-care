<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260607133852 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add care notes to schedules';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE care_schedule ADD notes VARCHAR(160) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE care_schedule DROP notes');
    }
}
