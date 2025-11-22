<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251122113850 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create rooms';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE room (
              id INT AUTO_INCREMENT NOT NULL,
              name VARCHAR(60) NOT NULL,
              household_id INT NOT NULL,
              INDEX IDX_729F519BE79FF843 (household_id),
              UNIQUE INDEX uniq_room_household_name (household_id, name),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              room
            ADD
              CONSTRAINT FK_729F519BE79FF843 FOREIGN KEY (household_id) REFERENCES household (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE room DROP FOREIGN KEY FK_729F519BE79FF843');
        $this->addSql('DROP TABLE room');
    }
}
