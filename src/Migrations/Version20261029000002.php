<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Valeurs de config par channel : colonne JSON nullable, aucune donnée existante modifiée.
 */
final class Version20261029000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add channel_values JSON column to config translations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sylius_happy_cms__config_translation ADD channel_values JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sylius_happy_cms__config_translation DROP channel_values');
    }
}
