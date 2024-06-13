<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240613122831 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE video ALTER views SET DEFAULT 0');
        $this->addSql('ALTER TABLE video ALTER downloads SET DEFAULT 0');
        $this->addSql('ALTER TABLE video ALTER delete_flag SET DEFAULT false');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE video ALTER views DROP DEFAULT');
        $this->addSql('ALTER TABLE video ALTER downloads DROP DEFAULT');
        $this->addSql('ALTER TABLE video ALTER delete_flag DROP DEFAULT');
    }
}
