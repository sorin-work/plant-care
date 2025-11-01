<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251101115551 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE app_user (
              id INT AUTO_INCREMENT NOT NULL,
              email VARCHAR(180) NOT NULL,
              display_name VARCHAR(80) NOT NULL,
              roles JSON NOT NULL,
              password VARCHAR(255) NOT NULL,
              created_at DATETIME NOT NULL,
              UNIQUE INDEX UNIQ_88BDF3E9E7927C74 (email),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_user');
    }
}
