<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240613163039 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE playlist ALTER page_viewed DROP NOT NULL');
        $this->addSql('ALTER TABLE playlist ALTER delete_flag DROP NOT NULL');
        $this->addSql('ALTER TABLE video ALTER downloads DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE playlist ALTER page_viewed SET NOT NULL');
        $this->addSql('ALTER TABLE playlist ALTER delete_flag SET NOT NULL');
        $this->addSql('ALTER TABLE video ALTER downloads SET NOT NULL');
    }
}
