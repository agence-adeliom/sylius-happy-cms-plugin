<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260109115902 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE orm_redirects (id INT AUTO_INCREMENT NOT NULL, host VARCHAR(255) NOT NULL, schemes JSON NOT NULL, methods JSON NOT NULL, defaults JSON NOT NULL, requirements JSON NOT NULL, options JSON NOT NULL, `condition` VARCHAR(255) NOT NULL, variablePattern VARCHAR(255) DEFAULT NULL, staticPrefix VARCHAR(255) DEFAULT NULL, routeName VARCHAR(255) NOT NULL, uri VARCHAR(255) DEFAULT NULL, permanent TINYINT(1) NOT NULL, routeTargetId INT DEFAULT NULL, UNIQUE INDEX UNIQ_6CA17E0391F30BA8 (routeName), INDEX IDX_6CA17E034C0848C6 (routeTargetId), INDEX IDX_6CA17E03A5B5867E (staticPrefix), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE orm_routes (id INT AUTO_INCREMENT NOT NULL, host VARCHAR(255) NOT NULL, schemes JSON NOT NULL, methods JSON NOT NULL, defaults JSON NOT NULL, requirements JSON NOT NULL, options JSON NOT NULL, `condition` VARCHAR(255) NOT NULL, variablePattern VARCHAR(255) DEFAULT NULL, staticPrefix VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, position INT NOT NULL, INDEX IDX_5793FCA5B5867E (staticPrefix), UNIQUE INDEX name_idx (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_happy_cms__config (id INT UNSIGNED AUTO_INCREMENT NOT NULL, config VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, type VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_672948FDD48A2F7C (config), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_happy_cms__config_translation (id INT UNSIGNED AUTO_INCREMENT NOT NULL, translatable_id INT UNSIGNED NOT NULL, value LONGTEXT DEFAULT NULL, locale VARCHAR(255) NOT NULL, INDEX IDX_D73724B62C2AC5D3 (translatable_id), UNIQUE INDEX sylius_happy_cms__config_translation_uniq_trans (translatable_id, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_happy_cms__folder (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(100) NOT NULL, INDEX IDX_5F016E4C727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_happy_cms__media (id INT AUTO_INCREMENT NOT NULL, folder_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(100) NOT NULL, mime VARCHAR(255) DEFAULT NULL, size INT DEFAULT NULL, lastModified INT DEFAULT NULL, metas JSON NOT NULL, INDEX IDX_AF310EF4162CB942 (folder_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_happy_cms__menu (id INT UNSIGNED AUTO_INCREMENT NOT NULL, root_item_id INT UNSIGNED DEFAULT NULL, code VARCHAR(30) NOT NULL, name VARCHAR(255) DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, status TINYINT(1) NOT NULL, INDEX IDX_DA917C2BFE2E1A94 (root_item_id), UNIQUE INDEX sylius_happy_cms__menu_code_unik (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_happy_cms__menu_item (id INT UNSIGNED AUTO_INCREMENT NOT NULL, menu_id INT UNSIGNED DEFAULT NULL, parent_id INT UNSIGNED DEFAULT NULL, lft INT NOT NULL, lvl INT NOT NULL, rgt INT NOT NULL, root INT DEFAULT NULL, class_attribute VARCHAR(255) DEFAULT NULL, position SMALLINT UNSIGNED DEFAULT NULL, target TINYINT(1) DEFAULT 0, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, publishState VARCHAR(100) DEFAULT NULL, publish_date DATETIME DEFAULT NULL, unpublish_date DATETIME DEFAULT NULL, INDEX IDX_52C0AB66CCD7E912 (menu_id), INDEX IDX_52C0AB66727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_happy_cms__menu_item_translation (id INT UNSIGNED AUTO_INCREMENT NOT NULL, translatable_id INT UNSIGNED NOT NULL, name VARCHAR(255) NOT NULL, url VARCHAR(255) DEFAULT NULL, locale VARCHAR(255) NOT NULL, INDEX IDX_DBFC64242C2AC5D3 (translatable_id), UNIQUE INDEX sylius_happy_cms__menu_item_translation_uniq_trans (translatable_id, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_happy_cms__page (id INT UNSIGNED AUTO_INCREMENT NOT NULL, parent_id INT UNSIGNED DEFAULT NULL, channel_id INT DEFAULT NULL, lft INT NOT NULL, lvl INT NOT NULL, rgt INT NOT NULL, root INT DEFAULT NULL, position SMALLINT UNSIGNED DEFAULT NULL, action VARCHAR(255) DEFAULT NULL, template VARCHAR(255) DEFAULT NULL, css LONGTEXT DEFAULT NULL, js LONGTEXT DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, publishState VARCHAR(100) DEFAULT NULL, publish_date DATETIME DEFAULT NULL, unpublish_date DATETIME DEFAULT NULL, INDEX IDX_B39EF098727ACA70 (parent_id), INDEX IDX_B39EF09872F5A1AA (channel_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_happy_cms__page_route (page_id INT UNSIGNED NOT NULL, routeinterface_id INT UNSIGNED NOT NULL, INDEX IDX_AB7ADE48C4663E4 (page_id), INDEX IDX_AB7ADE48CBD65B6D (routeinterface_id), PRIMARY KEY(page_id, routeinterface_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_happy_cms__page_content_block (id INT UNSIGNED AUTO_INCREMENT NOT NULL, page_id INT UNSIGNED DEFAULT NULL, type VARCHAR(255) NOT NULL, published_data JSON DEFAULT NULL, draft_data JSON DEFAULT NULL, locale VARCHAR(10) NOT NULL, position INT UNSIGNED DEFAULT 0 NOT NULL, preview_position INT UNSIGNED DEFAULT 0 NOT NULL, layer VARCHAR(100) DEFAULT NULL, deleted TINYINT(1) DEFAULT 0 NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, publishState VARCHAR(100) DEFAULT NULL, publish_date DATETIME DEFAULT NULL, unpublish_date DATETIME DEFAULT NULL, preview_publish_state VARCHAR(100) DEFAULT NULL, preview_publish_date DATETIME DEFAULT NULL, preview_unpublish_date DATETIME DEFAULT NULL, INDEX IDX_48095E75C4663E4 (page_id), INDEX idx_page_locale_position (page_id, locale, position), INDEX idx_page_locale_published (page_id, locale, publishState), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_happy_cms__page_translation (id INT UNSIGNED AUTO_INCREMENT NOT NULL, translatable_id INT UNSIGNED NOT NULL, content JSON DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, slug VARCHAR(255) DEFAULT NULL, seo_title LONGTEXT DEFAULT NULL, seo_description LONGTEXT DEFAULT NULL, seo_keywords VARCHAR(255) DEFAULT NULL, seo_canonical VARCHAR(255) DEFAULT NULL, seo_cover BIGINT DEFAULT NULL, seo_key VARCHAR(255) DEFAULT NULL, seo_sitemap TINYINT(1) NOT NULL, seo_robots JSON NOT NULL, locale VARCHAR(255) NOT NULL, INDEX IDX_A74F201D2C2AC5D3 (translatable_id), UNIQUE INDEX sylius_happy_cms__page_translation_uniq_trans (translatable_id, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_happy_cms__redirect_route (id INT UNSIGNED AUTO_INCREMENT NOT NULL, host VARCHAR(255) NOT NULL, schemes JSON NOT NULL, methods JSON NOT NULL, defaults JSON NOT NULL, requirements JSON NOT NULL, options JSON NOT NULL, `condition` VARCHAR(255) NOT NULL, variablePattern VARCHAR(255) DEFAULT NULL, staticPrefix VARCHAR(255) DEFAULT NULL, uri LONGTEXT DEFAULT NULL, routeName VARCHAR(255) DEFAULT NULL, permanent TINYINT(1) DEFAULT 0 NOT NULL, routeTargetId INT UNSIGNED DEFAULT NULL, UNIQUE INDEX UNIQ_4BF1D16B91F30BA8 (routeName), INDEX IDX_4BF1D16B4C0848C6 (routeTargetId), INDEX IDX_4BF1D16BA5B5867E (staticPrefix), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_happy_cms__route (id INT UNSIGNED AUTO_INCREMENT NOT NULL, host VARCHAR(255) NOT NULL, schemes JSON NOT NULL, methods JSON NOT NULL, defaults JSON NOT NULL, requirements JSON NOT NULL, options JSON NOT NULL, `condition` VARCHAR(255) NOT NULL, variablePattern VARCHAR(255) DEFAULT NULL, staticPrefix VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, position INT NOT NULL, lastModification DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_C7D98F815E237E06 (name), INDEX IDX_C7D98F81A5B5867E (staticPrefix), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_happy_cms__shared_block (id INT UNSIGNED AUTO_INCREMENT NOT NULL, block_key VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, status TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_1B4DE0BDE81B6293 (block_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_happy_cms__shared_block_translation (id INT UNSIGNED AUTO_INCREMENT NOT NULL, translatable_id INT UNSIGNED NOT NULL, content JSON DEFAULT NULL, locale VARCHAR(255) NOT NULL, INDEX IDX_62715C802C2AC5D3 (translatable_id), UNIQUE INDEX sylius_happy_cms__shared_block_translation_uniq_trans (translatable_id, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE orm_redirects ADD CONSTRAINT FK_6CA17E034C0848C6 FOREIGN KEY (routeTargetId) REFERENCES orm_routes (id)');
        $this->addSql('ALTER TABLE sylius_happy_cms__config_translation ADD CONSTRAINT FK_D73724B62C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES sylius_happy_cms__config (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sylius_happy_cms__folder ADD CONSTRAINT FK_5F016E4C727ACA70 FOREIGN KEY (parent_id) REFERENCES sylius_happy_cms__folder (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sylius_happy_cms__media ADD CONSTRAINT FK_AF310EF4162CB942 FOREIGN KEY (folder_id) REFERENCES sylius_happy_cms__folder (id)');
        $this->addSql('ALTER TABLE sylius_happy_cms__menu ADD CONSTRAINT FK_DA917C2BFE2E1A94 FOREIGN KEY (root_item_id) REFERENCES sylius_happy_cms__menu_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sylius_happy_cms__menu_item ADD CONSTRAINT FK_52C0AB66CCD7E912 FOREIGN KEY (menu_id) REFERENCES sylius_happy_cms__menu (id)');
        $this->addSql('ALTER TABLE sylius_happy_cms__menu_item ADD CONSTRAINT FK_52C0AB66727ACA70 FOREIGN KEY (parent_id) REFERENCES sylius_happy_cms__menu_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sylius_happy_cms__menu_item_translation ADD CONSTRAINT FK_DBFC64242C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES sylius_happy_cms__menu_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sylius_happy_cms__page ADD CONSTRAINT FK_B39EF098727ACA70 FOREIGN KEY (parent_id) REFERENCES sylius_happy_cms__page (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE sylius_happy_cms__page ADD CONSTRAINT FK_B39EF09872F5A1AA FOREIGN KEY (channel_id) REFERENCES sylius_channel (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE sylius_happy_cms__page_route ADD CONSTRAINT FK_AB7ADE48C4663E4 FOREIGN KEY (page_id) REFERENCES sylius_happy_cms__page (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sylius_happy_cms__page_route ADD CONSTRAINT FK_AB7ADE48CBD65B6D FOREIGN KEY (routeinterface_id) REFERENCES sylius_happy_cms__route (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sylius_happy_cms__page_content_block ADD CONSTRAINT FK_48095E75C4663E4 FOREIGN KEY (page_id) REFERENCES sylius_happy_cms__page (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE sylius_happy_cms__page_translation ADD CONSTRAINT FK_A74F201D2C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES sylius_happy_cms__page (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sylius_happy_cms__redirect_route ADD CONSTRAINT FK_4BF1D16B4C0848C6 FOREIGN KEY (routeTargetId) REFERENCES sylius_happy_cms__route (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE sylius_happy_cms__shared_block_translation ADD CONSTRAINT FK_62715C802C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES sylius_happy_cms__shared_block (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0 ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E0E3BD61CE ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E016BA31DB ON messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE orm_redirects DROP FOREIGN KEY FK_6CA17E034C0848C6');
        $this->addSql('ALTER TABLE sylius_happy_cms__config_translation DROP FOREIGN KEY FK_D73724B62C2AC5D3');
        $this->addSql('ALTER TABLE sylius_happy_cms__folder DROP FOREIGN KEY FK_5F016E4C727ACA70');
        $this->addSql('ALTER TABLE sylius_happy_cms__media DROP FOREIGN KEY FK_AF310EF4162CB942');
        $this->addSql('ALTER TABLE sylius_happy_cms__menu DROP FOREIGN KEY FK_DA917C2BFE2E1A94');
        $this->addSql('ALTER TABLE sylius_happy_cms__menu_item DROP FOREIGN KEY FK_52C0AB66CCD7E912');
        $this->addSql('ALTER TABLE sylius_happy_cms__menu_item DROP FOREIGN KEY FK_52C0AB66727ACA70');
        $this->addSql('ALTER TABLE sylius_happy_cms__menu_item_translation DROP FOREIGN KEY FK_DBFC64242C2AC5D3');
        $this->addSql('ALTER TABLE sylius_happy_cms__page DROP FOREIGN KEY FK_B39EF098727ACA70');
        $this->addSql('ALTER TABLE sylius_happy_cms__page DROP FOREIGN KEY FK_B39EF09872F5A1AA');
        $this->addSql('ALTER TABLE sylius_happy_cms__page_route DROP FOREIGN KEY FK_AB7ADE48C4663E4');
        $this->addSql('ALTER TABLE sylius_happy_cms__page_route DROP FOREIGN KEY FK_AB7ADE48CBD65B6D');
        $this->addSql('ALTER TABLE sylius_happy_cms__page_content_block DROP FOREIGN KEY FK_48095E75C4663E4');
        $this->addSql('ALTER TABLE sylius_happy_cms__page_translation DROP FOREIGN KEY FK_A74F201D2C2AC5D3');
        $this->addSql('ALTER TABLE sylius_happy_cms__redirect_route DROP FOREIGN KEY FK_4BF1D16B4C0848C6');
        $this->addSql('ALTER TABLE sylius_happy_cms__shared_block_translation DROP FOREIGN KEY FK_62715C802C2AC5D3');
        $this->addSql('DROP TABLE orm_redirects');
        $this->addSql('DROP TABLE orm_routes');
        $this->addSql('DROP TABLE sylius_happy_cms__config');
        $this->addSql('DROP TABLE sylius_happy_cms__config_translation');
        $this->addSql('DROP TABLE sylius_happy_cms__folder');
        $this->addSql('DROP TABLE sylius_happy_cms__media');
        $this->addSql('DROP TABLE sylius_happy_cms__menu');
        $this->addSql('DROP TABLE sylius_happy_cms__menu_item');
        $this->addSql('DROP TABLE sylius_happy_cms__menu_item_translation');
        $this->addSql('DROP TABLE sylius_happy_cms__page');
        $this->addSql('DROP TABLE sylius_happy_cms__page_route');
        $this->addSql('DROP TABLE sylius_happy_cms__page_content_block');
        $this->addSql('DROP TABLE sylius_happy_cms__page_translation');
        $this->addSql('DROP TABLE sylius_happy_cms__redirect_route');
        $this->addSql('DROP TABLE sylius_happy_cms__route');
        $this->addSql('DROP TABLE sylius_happy_cms__shared_block');
        $this->addSql('DROP TABLE sylius_happy_cms__shared_block_translation');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
    }
}
