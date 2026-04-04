<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260404134241 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add winter fertilizing pause';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE household ADD pause_fertilizing_in_winter TINYINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE household DROP pause_fertilizing_in_winter');
    }
}
