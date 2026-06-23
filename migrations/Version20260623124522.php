<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260623124522 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Index photo names';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_journal_photo ON journal_entry (photo)');
        $this->addSql('CREATE INDEX idx_plant_cover_photo ON plant (cover_photo)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_journal_photo ON journal_entry');
        $this->addSql('DROP INDEX idx_plant_cover_photo ON plant');
    }
}
