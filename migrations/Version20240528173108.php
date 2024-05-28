<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240528173108 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE playlist (uuid UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, deletion_data TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, page_viewed INT NOT NULL, delete_flag BOOLEAN NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('COMMENT ON COLUMN playlist.uuid IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN playlist.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN playlist.deletion_data IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE video (uuid UUID NOT NULL, playlist_uuid UUID NOT NULL, deletion_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, views INT NOT NULL, size INT NOT NULL, downloads INT NOT NULL, image_preview VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, path VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delete_flag BOOLEAN NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX IDX_7CC7DA2C716667C4 ON video (playlist_uuid)');
        $this->addSql('COMMENT ON COLUMN video.uuid IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN video.playlist_uuid IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN video.deletion_date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN video.size IS \'Размер файла в килобайтах\'');
        $this->addSql('COMMENT ON COLUMN video.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE video ADD CONSTRAINT FK_7CC7DA2C716667C4 FOREIGN KEY (playlist_uuid) REFERENCES playlist (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE video DROP CONSTRAINT FK_7CC7DA2C716667C4');
        $this->addSql('DROP TABLE playlist');
        $this->addSql('DROP TABLE video');
    }
}
