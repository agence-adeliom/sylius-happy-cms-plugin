<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Rattachement optionnel des menus, blocs partagés et configs aux channels Sylius.
 *
 * Crée uniquement les tables de jointure : aucune donnée existante n'est modifiée,
 * une relation vide reste équivalente à « tous les channels ».
 */
final class Version20261029000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add channel join tables for menu, shared block and config';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE sylius_happy_cms__config_channels (config_id INT UNSIGNED NOT NULL, channel_id INT NOT NULL, INDEX IDX_FC119EE24DB0683 (config_id), INDEX IDX_FC119EE72F5A1AA (channel_id), PRIMARY KEY (config_id, channel_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_happy_cms__shared_block_channels (shared_block_id INT UNSIGNED NOT NULL, channel_id INT NOT NULL, INDEX IDX_ABE2439CBCC9732F (shared_block_id), INDEX IDX_ABE2439C72F5A1AA (channel_id), PRIMARY KEY (shared_block_id, channel_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_happy_cms__menu_channels (menu_id INT UNSIGNED NOT NULL, channel_id INT NOT NULL, INDEX IDX_DC957DB6CCD7E912 (menu_id), INDEX IDX_DC957DB672F5A1AA (channel_id), PRIMARY KEY (menu_id, channel_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sylius_happy_cms__config_channels ADD CONSTRAINT FK_FC119EE24DB0683 FOREIGN KEY (config_id) REFERENCES sylius_happy_cms__config (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sylius_happy_cms__config_channels ADD CONSTRAINT FK_FC119EE72F5A1AA FOREIGN KEY (channel_id) REFERENCES sylius_channel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sylius_happy_cms__shared_block_channels ADD CONSTRAINT FK_ABE2439CBCC9732F FOREIGN KEY (shared_block_id) REFERENCES sylius_happy_cms__shared_block (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sylius_happy_cms__shared_block_channels ADD CONSTRAINT FK_ABE2439C72F5A1AA FOREIGN KEY (channel_id) REFERENCES sylius_channel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sylius_happy_cms__menu_channels ADD CONSTRAINT FK_DC957DB6CCD7E912 FOREIGN KEY (menu_id) REFERENCES sylius_happy_cms__menu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sylius_happy_cms__menu_channels ADD CONSTRAINT FK_DC957DB672F5A1AA FOREIGN KEY (channel_id) REFERENCES sylius_channel (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE sylius_happy_cms__config_channels');
        $this->addSql('DROP TABLE sylius_happy_cms__shared_block_channels');
        $this->addSql('DROP TABLE sylius_happy_cms__menu_channels');
    }
}
