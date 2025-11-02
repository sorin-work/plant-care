<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251102121513 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create households and memberships';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE household (
              id INT AUTO_INCREMENT NOT NULL,
              name VARCHAR(80) NOT NULL,
              timezone VARCHAR(64) NOT NULL,
              hemisphere VARCHAR(8) NOT NULL,
              created_at DATETIME NOT NULL,
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE membership (
              id INT AUTO_INCREMENT NOT NULL,
              role VARCHAR(16) NOT NULL,
              joined_at DATETIME NOT NULL,
              household_id INT NOT NULL,
              user_id INT NOT NULL,
              INDEX IDX_86FFD285E79FF843 (household_id),
              INDEX IDX_86FFD285A76ED395 (user_id),
              UNIQUE INDEX uniq_membership_household_user (household_id, user_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              membership
            ADD
              CONSTRAINT FK_86FFD285E79FF843 FOREIGN KEY (household_id) REFERENCES household (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              membership
            ADD
              CONSTRAINT FK_86FFD285A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE membership DROP FOREIGN KEY FK_86FFD285E79FF843');
        $this->addSql('ALTER TABLE membership DROP FOREIGN KEY FK_86FFD285A76ED395');
        $this->addSql('DROP TABLE household');
        $this->addSql('DROP TABLE membership');
    }
}
